<div class="box-body">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th> @lang('view_pages.s_no')</th>
                    <th> @lang('view_pages.name')</th>
                    <th>Foto</th>
                    <th> @lang('view_pages.area')</th>
                    <th> @lang('view_pages.email')</th>
                    <th> @lang('view_pages.mobile')</th>
                    <th>CPF</th>
                    <th>Data de Nascimento</th>
                    @if($app_for == "super" || $app_for == "bidding")
                        <th> @lang('view_pages.transport_type')</th>
                    @endif
                    <th>@lang('view_pages.document_view')</th>
                    <th> @lang('view_pages.approve_status')</th>
                    <th> @lang('view_pages.declined_reason')</th>
                    <th> @lang('view_pages.action')</th>
                </tr>
            </thead>
            <tbody>
                @if(count($results)<1)
                    <tr>
                        <td colspan="12">
                            <p id="no_data" class="lead no-data text-center">
                                <img src="{{asset('assets/img/dark-data.svg')}}" style="width:150px;margin-top:25px;margin-bottom:25px;" alt="">
                                <h4 class="text-center" style="color:#333;font-size:25px;">@lang('view_pages.no_data_found')</h4>
                            </p>
                        </tr>
                @else
                    @php  $i= $results->firstItem(); @endphp
                    @foreach($results as $key => $result)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{$result->name}}</td>
                            <td>
                                @if($result->profile_pic)
                                    <img src="{{ asset('storage/uploads/driver/profile-picture/'.$result->profile_pic) }}" alt="Profile Picture" style="width: 50px; height: 50px; border-radius: 50%;">
                                @else
                                    Sem foto
                                @endif
                            </td>
                            @if($result->serviceLocation)
                                <td>{{$result->serviceLocation->name}}</td>
                            @else
                                <td>--</td>
                            @endif
                            @if(env('APP_FOR')=='demo')
                                <td>**********</td>
                            @else
                                <td>{{$result->email}}</td>
                            @endif
                            @if(env('APP_FOR')=='demo')
                                <td>**********</td>
                            @else
                                <td>{{$result->mobile}}</td>
                            @endif
                            <td>{{$result->cpf}}</td>
                            <td>
                                @if($result->data_nascimento && $result->data_nascimento != '0000-00-00' && $result->data_nascimento != '-000-11-30')
                                    {{ \Carbon\Carbon::parse($result->data_nascimento)->format('d/m/Y') }}
                                @else
                                    --
                                @endif
                            </td>
                            @if($app_for == "super" || $app_for == "bidding")
                                <td>{{$result->transport_type}}</td>
                            @endif
                            <td>
                                @if(auth()->user()->can('driver-document'))         
                                    <a href="{{ url('drivers/document/view',$result->id) }}" class="btn btn-social-icon btn-bitbucket">
                                        <i class="fa fa-file-text"></i>
                                @endif
                                </a>
                            </td>
                            @if($result->approve)
                                <td><button class="btn btn-success btn-sm">{{ trans('view_pages.approved') }}</button></td>
                            @else
                                <td><button class="btn btn-danger btn-sm">{{ trans('view_pages.disapproved') }}</button></td>
                            @endif
                            @if($result->reason)
                                <td>{{$result->reason}}</td>
                            @else
                                <td>--</td>
                            @endif
                            <td>
                                <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('view_pages.action')</button>
                                <div class="dropdown-menu">
                                    @if(auth()->user()->can('edit-drivers'))         
                                        <a class="dropdown-item" href="{{url('drivers',$result->id)}}">
                                            <i class="fa fa-pencil"></i>@lang('view_pages.edit')
                                        </a>
                                    @endif
                                    @if(auth()->user()->can('toggle-drivers'))         
                                        <a class="dropdown-item decline" data-reason="{{ $result->reason }}" data-id="{{ $result->id }}" href="{{url('drivers/toggle_approve',['driver'=>$result->id,'approval_status'=>0])}}">
                                            <i class="fa fa-dot-circle-o"></i>@lang('view_pages.disapproved')
                                        </a>
                                        <a class="dropdown-item" href="{{url('drivers/toggle_approve',['driver'=>$result->id,'approval_status'=>1])}}">
                                            <i class="fa fa-dot-circle-o"></i>@lang('view_pages.approved')
                                        </a>
                                    @endif
                                    @if(auth()->user()->can('delete-drivers'))         
                                        <a class="dropdown-item sweet-delete" href="#" data-url="{{url('drivers/delete',$result->id)}}">
                                            <i class="fa fa-trash-o"></i>@lang('view_pages.delete')
                                        </a> 
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        <div class="text-right">
            <span style="float:right">{{$results->links()}}</span>
        </div>
    </div>
</div>
