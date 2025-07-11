<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;

/**
 * Test filter to demonstrate the custom filter usage.
 * Delete later.
 */
class DriverFilter implements FilterContract {
    /**
     * The available filters.
     *
     * @return array
     */
    public function filters() {
        return [
            'active','approve','available','date_option','vehicle_type','area', 'search'
        ];
    }
    
    // ...

    public function search($builder, $value) {
        $builder->where(function ($query) use ($value) {
            $query->where('name', 'like', '%' . $value . '%')
                ->orWhere('email', 'like', '%' . $value . '%')
                ->orWhere('mobile', 'like', '%' . $value . '%')
                ->orWhere('cpf', 'like', '%' . $value . '%');
        });
    }

    public function active($builder, $value = 0) {
        $builder->whereHas('user', function($q)use($value){
            $q->where('active',$value);
        });
    }
    
    public function approve($builder, $value = 0) {
        $builder->where('approve', $value);
    }
    
    public function available($builder, $value = 0) {
        $builder->where('available', $value);
    }

    public function date_option($builder, $value = 0) {
        $today = now();
        $to = now()->endOfDay()->toDateTimeString();

        if($value == 'today'){
            $from = $today->startOfDay()->toDateTimeString();
        }elseif($value == 'week'){
            $from = $today->startOfWeek()->toDateTimeString();
        }elseif($value == 'month'){
            $from = $today->startOfMonth()->toDateTimeString();
        }elseif($value == 'year'){
            $from = $today->startOfYear()->toDateTimeString();
        }else{
            $date = explode('<>', $value);
            $from = Carbon::parse($date[0])->toDateTimeString();
            $to = Carbon::parse($date[1])->endOfDay()->toDateTimeString();
        }

        $builder->whereBetween('created_at', [$from,$to]);
    }
    
    public function vehicle_type($builder, $value = 0) {
        $builder->whereHas('driverVehicleTypeDetail', function ($q) use ($value) {
            $q->where('vehicle_type', $value);
        });
    }

    public function area($builder, $value = 'all'){
        if($value != 'all'){
            $builder->where('service_location_id',$value);
        }
    }
}
