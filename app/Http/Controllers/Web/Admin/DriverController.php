<?php

namespace App\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Country;
use App\Jobs\NotifyViaMqtt;
use App\Models\Admin\Driver;
use Illuminate\Http\Request;
use App\Jobs\NotifyViaSocket;
use App\Models\Admin\Company;
use App\Models\Master\CarMake;
use App\Models\Master\CarModel;
use App\Base\Constants\Auth\Role;
use App\Models\Admin\VehicleType;
use App\Models\Admin\ServiceLocation;
use App\Http\Controllers\ApiController;
use App\Base\Filters\Admin\DriverFilter;
use App\Base\Constants\Masters\PushEnums;
use App\Models\Admin\DriverNeededDocument;
use App\Http\Controllers\Web\BaseController;
use App\Base\Constants\Auth\Role as RoleSlug;
use App\Transformers\Driver\DriverTransformer;
use App\Base\Filters\Master\CommonMasterFilter;
use App\Jobs\Notifications\AndroidPushNotification;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Http\Requests\Admin\Driver\CreateDriverRequest;
use App\Http\Requests\Admin\Driver\UpdateDriverRequest;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Models\Request\Request as RequestRequest;
use App\Models\Request\RequestRating;
use App\Base\Filters\Admin\RequestFilter;
use App\Models\Payment\DriverWalletHistory;
use App\Models\Payment\DriverWallet;
use App\Http\Requests\Admin\Driver\AddDriverMoneyToWalletRequest;
use App\Transformers\Payment\DriverWalletHistoryTransformer;
use App\Base\Constants\Masters\WalletRemarks;
use Illuminate\Support\Str;
use App\Models\Payment\WalletWithdrawalRequest;
use App\Models\Payment\UserWalletHistory;
use App\Base\Constants\Setting\Settings;
use Kreait\Firebase\Contract\Database;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Admin\DriverVehicleType;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @resource Driver
 *
 * Controller para gerenciamento de motoristas
 */
class DriverController extends BaseController
{
    protected $driver;
    protected $imageUploader;
    protected $user;
    protected $country;
    protected $gateway;
    protected $database;

    public function __construct(Driver $driver, ImageUploaderContract $imageUploader, User $user, Country $country, Database $database)
    {
        $this->driver = $driver;
        $this->imageUploader = $imageUploader;
        $this->user = $user;
        $this->country = $country;
        $this->database = $database;
        $this->gateway = env('PAYMENT_GATEWAY');
    }

    public function index()
    {
        $page = trans('pages_names.drivers');
        $main_menu = 'drivers';
        $sub_menu = 'driver_details';
        $services = ServiceLocation::whereActive(true)->companyKey()->get();
        $approved = Driver::where('approve', true)->where('owner_id', null)->get();
        return view('admin.drivers.index', compact('page', 'main_menu', 'sub_menu', 'services', 'approved'));
    }

    public function getApprovedDrivers(QueryFilterContract $queryFilter)
    {
        $app_for = config('app.app_for');
        if (access()->hasRole(RoleSlug::SUPER_ADMIN)) {
            $query = Driver::where('approve', true)->where('owner_id', null)->orderBy('created_at', 'desc');
            if (env('APP_FOR') == 'demo') {
                $query->whereHas('user', function ($query) {
                    $query->whereCompanyKey(auth()->user()->company_key);
                });
            }
        } else {
            $this->validateAdmin();
            $query = $this->driver->where('approve', true)->where('owner_id', null)
                ->where('service_location_id', auth()->user()->admin->service_location_id)->orderBy('created_at', 'desc');
        }
        $results = $queryFilter->builder($query)->customFilter(new DriverFilter)->paginate();
        return view('admin.drivers._drivers', compact('results', 'app_for'))->render();
    }

    public function approvalPending()
    {
        $page = trans('pages_names.drivers');
        $main_menu = 'drivers';
        $sub_menu = 'driver_approval_pending';
        $services = ServiceLocation::whereActive(true)->companyKey()->get();
        $app_for = config('app.app_for');
        return view('admin.drivers.pending-for-approval', compact('page', 'main_menu', 'app_for', 'sub_menu', 'services'));
    }

    public function getApprovalPendingDrivers(QueryFilterContract $queryFilter)
    {
        $app_for = config('app.app_for');
        $search_cpf = request()->input('cpf');
        $search_keyword = request()->input('search');

        // Log para depuração
        Log::info("Parâmetros de busca recebidos: CPF={$search_cpf}, Keyword={$search_keyword}");

// Construir a query base
        if (access()->hasRole(RoleSlug::SUPER_ADMIN)) {
            $query = Driver::where('approve', false)->where('owner_id', null)->orderBy('created_at', 'desc');
            if (env('APP_FOR') == 'demo') {
                $query->whereHas('user', function ($query) {
                    $query->whereCompanyKey(auth()->user()->company_key);
                });
            }
        } else {
            $this->validateAdmin();
            $query = $this->driver->where('approve', false)->where('owner_id', null)
                ->where('service_location_id', auth()->user()->admin->service_location_id)->orderBy('created_at', 'desc');
        }

        // Aplicar filtros
        if ($search_cpf) {
            $query->where('cpf', 'like', "%{$search_cpf}%");
        }

        if ($search_keyword) {
            $query->where(function ($q) use ($search_keyword) {
                $q->where('name', 'like', "%{$search_keyword}%")
                  ->orWhere('email', 'like', "%{$search_keyword}%")
                  ->orWhere('mobile', 'like', "%{$search_keyword}%");
            });
        }

        // Executar a query com paginação
        $results = $queryFilter->builder($query)->customFilter(new DriverFilter)->paginate();

        return view('admin.drivers._drivers', compact('results', 'app_for'))->render();
    }

    public function create()
    {
        $page = trans('pages_names.add_driver');
        $services = ServiceLocation::companyKey()->whereActive(true)->get();
        $types = VehicleType::whereActive(true)->get();
        $countries = Country::all();
        $carmake = CarMake::active()->get();
        $app_for = config('app.app_for');
        $companies = Company::active()->get();
        $main_menu = 'drivers';
        $sub_menu = 'driver_details';
        return view('admin.drivers.create', compact('services', 'types', 'page', 'countries', 'app_for', 'main_menu', 'sub_menu', 'companies', 'carmake'));
    }

    public function store(CreateDriverRequest $request)
    {
        Log::info('Dados do formulário de criação de motorista:', $request->all());
        
        $created_params = $request->only(['service_location_id', 'name', 'mobile', 'email', 'address', 'gender', 'car_make', 'car_model', 'custom_make', 'custom_model', 'car_color', 'car_number', 'cpf', 'data_nascimento']);
        
        if ($request->has('transport_type')) {
            $created_params['transport_type'] = $request->transport_type;
        }

        $validate_exists_email = $this->user->belongsTorole(Role::DRIVER)->where('email', $request->email)->exists();
        $validate_exists_mobile = $this->user->belongsTorole(Role::DRIVER)->where('mobile', $request->mobile)->exists();

        if ($validate_exists_email) {
            return redirect()->back()->withErrors(['email' => 'O e-mail fornecido já está em uso'])->withInput();
        }
        if ($validate_exists_mobile) {
            return redirect()->back()->withErrors(['mobile' => 'O número de celular fornecido já está em uso'])->withInput();
        }

        $created_params['uuid'] = driver_uuid();
        $created_params['owner_id'] = null;

        $country = Country::where('dial_code', $request->dial_code)->first();

        $user = $this->user->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'mobile_confirmed' => true,
            'password' => bcrypt($request->input('password')),
            'company_key' => auth()->user()->company_key,
            'refferal_code' => str_random(6),
            'country' => $country->id,
        ]);

        if ($uploadedFile = $this->getValidatedUpload('profile_picture', $request)) {
            $created_params['profile_pic'] = $this->imageUploader->file($uploadedFile)->saveDriverProfilePicture();
        }

        $user->attachRole(RoleSlug::DRIVER);

        $created_params['active'] = false;
        $created_params['country'] = $country->id;

        $driver = $user->driver()->create($created_params);

        $driver_detail_data = $request->only(['is_company_driver', 'company']);
        $driver->driverDetail()->create($driver_detail_data);

        foreach ($request->input('type') as $type) {
            DriverVehicleType::create(['driver_id' => $driver->id, 'vehicle_type' => $type]);
        }

        $driver->driverWallet()->create(['amount_added' => 0]);

        $message = trans('succes_messages.driver_added_succesfully');
        cache()->tags('drivers_list')->flush();

        return redirect('drivers')->with('success', $message);
    }

    public function getById(Driver $driver)
    {
        $page = trans('pages_names.edit_driver');
        $services = ServiceLocation::whereActive(true)->get();
        $types = VehicleType::whereActive(true)->get();
        $countries = Country::all();
        $companies = Company::active()->get();
        $item = $driver;
        $app_for = config('app.app_for');
        if ($app_for !== "taxi" && $app_for !== "delivery") {
            $carmake = CarMake::active()->whereTransportType($item->transport_type)->get();
        } else {
            $carmake = CarMake::active()->get();
        }
        $carmodel = CarModel::active()->whereMakeId($item->car_make)->get();
        $main_menu = 'drivers';
        $sub_menu = 'driver_details';
        return view('admin.drivers.update', compact('item', 'services', 'types', 'page', 'app_for', 'countries', 'main_menu', 'sub_menu', 'companies', 'carmake', 'carmodel'));
    }

    public function update(Driver $driver, UpdateDriverRequest $request)
    {
        Log::info('Dados do formulário de atualização de motorista:', $request->all());
        
        $updatedParams = $request->only(['service_location_id', 'name', 'mobile', 'email', 'gender', 'car_make', 'car_model', 'car_color', 'custom_make', 'custom_model', 'car_number', 'cpf', 'data_nascimento']);
        
        if ($request->has('transport_type')) {
            $updatedParams['transport_type'] = $request->transport_type;
        }

        $user = $driver->user;
        $validate_exists_email = $this->user->belongsTorole(Role::DRIVER)->where('email', $request->email)->where('id', '!=', $user->id)->exists();
        $validate_exists_mobile = $this->user->belongsTorole(Role::DRIVER)->where('mobile', $request->mobile)->where('id', '!=', $user->id)->exists();

        if ($validate_exists_email) {
            return redirect()->back()->withErrors(['email' => 'O e-mail fornecido já está em uso'])->withInput();
        }
        if ($validate_exists_mobile) {
            return redirect()->back()->withErrors(['mobile' => 'O número de celular fornecido já está em uso'])->withInput();
        }

        $user_param = $request->only(['profile']);
        $user_param['profile'] = null;

        if ($uploadedFile = $this->getValidatedUpload('profile_picture', $request)) {
            $user_param['profile'] = $this->imageUploader->file($uploadedFile)->saveProfilePicture();
        }

        $country = Country::where('dial_code', $request->dial_code)->first();
        $driverdata = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'country' => $country->id,
            'mobile' => $request->input('mobile'),
            'car_make' => $request->input('car_make'),
            'car_model' => $request->input('car_model'),
            'car_color' => $request->input('car_color'),
            'car_number' => $request->input('car_number'),
            'service_location_id' => $request->service_location_id,
            'custom_make' => $request->input('custom_make'),
            'custom_model' => $request->input('custom_model'),
            'gender' => $request->input('gender'),
            'cpf' => $request->input('cpf'),
            'data_nascimento' => $request->input('data_nascimento'),
        ];

        if (config('app.app_for') !== 'taxi' && config('app.app_for') !== 'delivery') {
            $driverdata['transport_type'] = $request->input('transport_type');
        }

        $driver->update($driverdata);

        $user_params = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'profile_picture' => $user_param['profile']
        ];
        if ($request->input('password')) {
            $user_params['password'] = bcrypt($request->input('password'));
        }
        $driver->user->update($user_params);

        $driverVehicleTypes = $driver->driverVehicleTypeDetail()->get();
        foreach ($driverVehicleTypes as $driverVehicleType) {
            $driverVehicleType->delete();
        }

        foreach ($request->type as $type) {
            DriverVehicleType::create(['driver_id' => $driver->id, 'vehicle_type' => $type]);
        }

        $message = trans('succes_messages.driver_added_succesfully');
        cache()->tags('drivers_list')->flush();
        return redirect('drivers')->with('success', $message);
    }

    public function toggleStatus(Driver $driver)
    {
        $status = $driver->active == 1 ? 0 : 1;
        $driver->update(['active' => $status]);
        $message = trans('succes_messages.driver_status_changed_succesfully');
        return redirect('drivers')->with('success', $message);
    }

    public function toggleApprove(Driver $driver, $approval_status)
    {
        $status = (boolean)$approval_status;

        if ($status) {
            $err = false;
            $neededDoc = DriverNeededDocument::count();
            $uploadedDoc = count($driver->driverDocument);

            if ($neededDoc != $uploadedDoc) {
                return redirect('drivers/document/view/' . $driver->id);
            }

            foreach ($driver->driverDocument as $driverDoc) {
                if ($driverDoc->document_status != 1) {
                    $err = true;
                }
            }

            if ($err) {
                $message = trans('succes_messages.driver_document_not_approved');
                return redirect('drivers/document/view/' . $driver->id);
            }
            $driver->update(['reason' => null]);
        }

        $this->database->getReference('drivers/driver_' . $driver->id)->update([
            'approve' => (int)$status,
            'updated_at' => Database::SERVER_TIMESTAMP
        ]);

        $driver->update(['approve' => $status]);

        $message = trans('succes_messages.driver_approve_status_changed_succesfully');
        $user = User::find($driver->user_id);
        if ($status) {
            $title = trans('push_notifications.driver_approved', [], $user->lang);
            $body = trans('push_notifications.driver_approved_body', [], $user->lang);
            $push_data = ['notification_enum' => PushEnums::DRIVER_ACCOUNT_APPROVED];
            $socket_success_message = PushEnums::DRIVER_ACCOUNT_APPROVED;
        } else {
            $title = trans('push_notifications.driver_declined_title', [], $user->lang);
            $body = trans('push_notifications.driver_declined_body', [], $user->lang);
            $push_data = ['notification_enum' => PushEnums::DRIVER_ACCOUNT_DECLINED];
            $socket_success_message = PushEnums::DRIVER_ACCOUNT_DECLINED;
        }

        $driver_details = $user->driver;
        $driver_result = fractal($driver_details, new DriverTransformer);
        $formated_driver = $this->formatResponseData($driver_result);
        $socket_params = $formated_driver['data'];
        $socket_data = new \stdClass();
        $socket_data->success = true;
        $socket_data->success_message = $socket_success_message;
        $socket_data->data = $socket_params;

        dispatch(new SendPushNotification($user, $title, $body));

        return redirect('drivers')->with('success', $message);
    }

    public function toggleAvailable(Driver $driver)
    {
        $status = $driver->available == 1 ? 0 : 1;
        $driver->update(['available' => $status]);
        $message = trans('succes_messages.driver_available_status_changed_succesfully');
        return redirect('drivers')->with('success', $message);
    }

    public function deletedDrivers()
    {
        $page = trans('pages_names.drivers');
        $main_menu = 'drivers';
        $sub_menu = 'deleted_drivers';
        return view('admin.drivers.deleted', compact('page', 'main_menu', 'sub_menu'));
    }

    public function revertById(Driver $driver)
    {
        $driver->user->update(['is_deleted_at' => null]);
        $message = trans('succes_messages.driver_reverted_succesfully');
        return redirect('drivers/deleted_drivers')->with('success', $message);
    }

    public function getDeletedDrivers(QueryFilterContract $queryFilter)
    {
        $app_for = config('app.app_for');
        $query = User::whereNotNull('is_deleted_at')->belongsToRole(RoleSlug::DRIVER);
        $results = $queryFilter->builder($query)->customFilter(new CommonMasterFilter)->paginate();
        return view('admin.drivers._deleted', compact('results', 'app_for'))->render();
    }

    public function delete(Driver $driver)
    {
        if (env('APP_FOR') == 'demo') {
            return $message = 'Você não pode excluir o motorista. Esta é a versão demo';
        }

        try {
            DB::beginTransaction();
            ChatMessage::whereIn('chat_id', function ($query) use ($driver) {
                $query->select('id')->from('chats')->where('user_id', $driver->user->id);
            })->delete();
            Chat::where('user_id', $driver->user->id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $this->database->getReference('drivers/driver_' . $driver->id)->remove();
        $driver->user()->delete();

        $message = trans('succes_messages.driver_deleted_succesfully');
        return redirect('drivers')->with('success', $message);
    }

    public function getCarMake()
    {
        $type = request()->all();
        $vehicletype = VehicleType::whereId($type)->first();
        if (config('app.app_for') == "bidding" || config('app.app_for') == "super") {
            $carmake = CarMake::active()->whereTransportType($vehicletype->is_taxi)->get();
        } else {
            $carmake = CarMake::active()->get();
        }
        return $carmake;
    }

    public function getType()
    {
        if (config('app.app_for') == "bidding" || config('app.app_for') == "super") {
            $type = request()->transport_type;
            return VehicleType::active()->whereIsTaxi($type)->get();
        } else {
            return VehicleType::where('active', '1')->get();
        }
    }

    public function getCarModel()
    {
        $carModel = request()->car_make;
        return CarModel::active()->whereMakeId($carModel)->get();
    }

    public function UpdateDriverDeclineReason(Request $request)
    {
        $driver = Driver::whereId($request->id)->update(['reason' => $request->reason]);
        return 'success';
    }

    public function DriverTripRequestIndex(Driver $driver)
    {
        $completedTrips = RequestRequest::where('driver_id', $driver->id)->companyKey()->whereIsCompleted(true)->count();
        $cancelledTrips = RequestRequest::where('driver_id', $driver->id)->companyKey()->whereIsCancelled(true)->count();

        $card = [];
        $card['completed_trip'] = ['name' => 'trips_completed', 'display_name' => 'Viagens Concluídas', 'count' => $completedTrips, 'icon' => 'fa fa-flag-checkered text-green'];
        $card['cancelled_trip'] = ['name' => 'trips_cancelled', 'display_name' => 'Viagens Canceladas', 'count' => $cancelledTrips, 'icon' => 'fa fa-ban text-red'];

        $main_menu = 'drivers';
        $sub_menu = 'driver_details';
        $items = $driver->id;

        return view('admin.drivers.driver-request-list', compact('card', 'main_menu', 'sub_menu', 'items'));
    }

    public function DriverTripRequest(QueryFilterContract $queryFilter, Driver $driver)
    {
        $items = $driver->id;
        $query = RequestRequest::where('driver_id', $driver->id);
        $results = $queryFilter->builder($query)->customFilter(new RequestFilter)->defaultSort('-created_at')->paginate();
        return view('admin.drivers.driver-request-list-view', compact('results', 'items'));
    }

    public function DriverPaymentHistory(Driver $driver)
    {
        $main_menu = 'drivers';
        $sub_menu = 'driver_details';
        $item = $driver;
        $bankInfo = $driver->user->bankInfo;
        $amount = DriverWallet::where('user_id', $driver->id)->first();

        if ($amount == null) {
            $card = [];
            $card['total_amount'] = ['name' => 'total_amount', 'display_name' => 'Total Adicionado', 'count' => "0", 'icon' => 'fa fa-flag-checkered text-green'];
            $card['amount_spent'] = ['name' => 'amount_spent', 'display_name' => 'Total Gasto', 'count' => "0", 'icon' => 'fa fa-ban text-red'];
            $card['balance_amount'] = ['name' => 'balance_amount', 'display_name' => 'Saldo', 'count' => "0", 'icon' => 'fa fa-ban text-red'];
            $history = UserWalletHistory::where('user_id', $driver->id)->orderBy('created_at', 'desc')->paginate(10);
        } else {
            $card = [];
            $card['total_amount'] = ['name' => 'total_amount', 'display_name' => 'Total Adicionado', 'count' => $amount->amount_added, 'icon' => 'fa fa-flag-checkered text-green'];
            $card['amount_spent'] = ['name' => 'amount_spent', 'display_name' => 'Total Gasto', 'count' => $amount->amount_spent, 'icon' => 'fa fa-ban text-red'];
            $card['balance_amount'] = ['name' => 'balance_amount', 'display_name' => 'Saldo', 'count' => $amount->amount_balance, 'icon' => 'fa fa-ban text-red'];
            $history = DriverWalletHistory::where('user_id', $driver->id)->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('admin.drivers.driver-payment-wallet', compact('card', 'main_menu', 'sub_menu', 'item', 'history', 'bankInfo'));
    }

    public function StoreDriverPaymentHistory(AddDriverMoneyToWalletRequest $request, Driver $driver)
    {
        $currency = get_settings(Settings::CURRENCY);
        $transaction_id = Str::random(6);

        $wallet_model = new DriverWallet();
        $wallet_add_history_model = new DriverWalletHistory();
        $user_id = $driver->id;

        $user_wallet = $wallet_model::firstOrCreate(['user_id' => $user_id]);
        $user_wallet->amount_added += $request->amount;
        $user_wallet->amount_balance += $request->amount;
        $user_wallet->save();

        $wallet_add_history_model::create([
            'user_id' => $user_id,
            'card_id' => null,
            'amount' => $request->amount,
            'transaction_id' => $transaction_id,
            'merchant' => null,
            'remarks' => WalletRemarks::MONEY_DEPOSITED_TO_E_WALLET_FROM_ADMIN,
            'is_credit' => true
        ]);

        $message = "Dinheiro adicionado com sucesso";
        return redirect()->back()->with('success', $message);
    }

    public function driverRatings()
    {
        $page = trans('pages_names.drivers');
        $main_menu = 'drivers';
        $sub_menu = 'driver_ratings';
        return view('admin.drivers.driver-ratings', compact('page', 'main_menu', 'sub_menu'));
    }

    public function fetchDriverRatings(QueryFilterContract $queryFilter)
    {
        $app_for = config('app.app_for');
        $query = Driver::query();
        $results = $queryFilter->builder($query)->customFilter(new DriverFilter)->paginate();
        return view('admin.drivers._driver-ratings', compact('results', 'app_for'))->render();
    }

    public function driverRatingView(Driver $driver)
    {
        $page = trans('pages_names.drivers');
        $main_menu = 'drivers';
        $sub_menu = 'driver_ratings';
        $trips = RequestRating::where('driver_id', $driver->id)->whereNotNull('user_id')->whereUserRating(true)->paginate(10);
        $item = $driver;
        return view('admin.drivers.driver-rating-view', compact('page', 'main_menu', 'sub_menu', 'item', 'trips'));
    }

    public function withdrawalRequestsList()
    {
        $page = trans('pages_names.withdrawal_requests');
        $main_menu = 'drivers';
        $sub_menu = 'withdrawal_requests';

        if (access()->hasRole(RoleSlug::SUPER_ADMIN)) {
            $history = WalletWithdrawalRequest::whereHas('driverDetail.user', function ($query) {
                $query->companyKey();
            })->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $admin_data = auth()->user()->admin;
            $history = WalletWithdrawalRequest::whereHas('driverDetail.user', function ($query) {
                $query->companyKey();
            })->whereHas('driverDetail', function ($query) use ($admin_data) {
                $query->where('service_location_id', $admin_data->service_location_id);
            })->orderBy('created_at', 'desc')->paginate(20);
        }

        return view('admin.drivers.driver-wallet-withdrawal-requests-list', compact('page', 'main_menu', 'sub_menu', 'history'));
    }

    public function withdrawalRequestDetail(Driver $driver)
    {
        $page = trans('pages_names.withdrawal_requests');
        $main_menu = 'drivers';
        $sub_menu = 'withdrawal_requests';
        $bankInfo = $driver->user->bankInfo;
        $history = WalletWithdrawalRequest::whereHas('driverDetail.user', function ($query) {
            $query->companyKey();
        })->where('driver_id', $driver->id)->orderBy('created_at', 'desc')->paginate(20);
        $amount = DriverWallet::where('user_id', $driver->id)->first();

        $card = [];
        $card['balance_amount'] = ['name' => 'balance_amount', 'display_name' => 'Saldo', 'count' => $amount->amount_balance, 'icon' => 'fa fa-ban text-red'];

        return view('admin.drivers.DriverWalletWithdrawalRequestDetail', compact('page', 'main_menu', 'sub_menu', 'history', 'card', 'bankInfo'));
    }

    public function approveWithdrawalRequest(WalletWithdrawalRequest $wallet_withdrawal_request)
    {
        $driver_wallet = DriverWallet::firstOrCreate(['user_id' => $wallet_withdrawal_request->driver_id]);
        $driver_wallet->amount_spent += $wallet_withdrawal_request->requested_amount;
        $driver_wallet->amount_balance -= $wallet_withdrawal_request->requested_amount;
        $driver_wallet->save();

        $wallet_withdrawal_request->driverDetail->driverWalletHistory()->create([
            'amount' => $wallet_withdrawal_request->requested_amount,
            'transaction_id' => str_random(6),
            'remarks' => WalletRemarks::WITHDRAWN_FROM_WALLET,
            'is_credit' => false
        ]);

        $wallet_withdrawal_request->status = 1;
        $wallet_withdrawal_request->save();

        $message = "Solicitação de saque aprovada com sucesso";
        return redirect()->back()->with('success', $message);
    }

    public function declineWithdrawalRequest(WalletWithdrawalRequest $wallet_withdrawal_request)
    {
        $wallet_withdrawal_request->status = 2;
        $wallet_withdrawal_request->save();

        $message = "Solicitação de saque recusada com sucesso";
        return redirect()->back()->with('success', $message);
    }

    public function NeagtiveBalanceDrivers()
    {
        $page = trans('pages_names.negative_balance_drivers');
        $main_menu = 'drivers';
        $sub_menu = 'negative_balance_drivers';
        $services = ServiceLocation::whereActive(true)->companyKey()->get();
        $approved = Driver::where('approve', true)->where('owner_id', null)->get();
        return view('admin.drivers.negative-balance-drivers', compact('page', 'main_menu', 'sub_menu', 'services', 'approved'));
    }

    public function NegativeBalanceFetch(QueryFilterContract $queryFilter)
    {
        $url = request()->fullUrl();
        $threshould_value = get_settings(Settings::DRIVER_WALLET_MINIMUM_AMOUNT_TO_GET_ORDER);

        return cache()->tags('drivers_list')->remember($url, Carbon::parse('10 minutes'), function () use ($queryFilter, $threshould_value) {
            if (access()->hasRole(RoleSlug::SUPER_ADMIN)) {
                $query = Driver::orderBy('created_at', 'desc')->where('owner_id', null)->whereHas('driverWallet', function ($query) use ($threshould_value) {
                    $query->where('amount_balance', '<=', $threshould_value);
                });

                if (env('APP_FOR') == 'demo') {
                    $query->whereHas('user', function ($query) {
                        $query->whereCompanyKey(auth()->user()->company_key);
                    });
                }
            } else {
                $this->validateAdmin();
                $query = $this->driver->where('service_location_id', auth()->user()->admin->service_location_id)
                    ->whereHas('driverWallet', function ($query) use ($threshould_value) {
                        $query->where('amount_balance', '<=', $threshould_value);
                    })->orderBy('created_at', 'desc');
            }
            $results = $queryFilter->builder($query)->customFilter(new DriverFilter)->paginate();
            return view('admin.drivers._drivers-negative-balance', compact('results'))->render();
        });
    }
}
