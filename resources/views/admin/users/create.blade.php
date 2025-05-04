@extends('admin.layouts.app')
@section('title', 'Main page')

@section('content')
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <a href="{{ url('users') }}">
                                <button class="btn btn-danger btn-sm pull-right" type="submit">
                                    <i class="mdi mdi-keyboard-backspace mr-2"></i>
                                    @lang('view_pages.back')
                                </button>
                            </a>
                        </div>

                        <div class="col-sm-12">
                            <form method="post" class="form-horizontal" action="{{ url('users/store') }}"
                                enctype="multipart/form-data">
                                {{ csrf_field() }}

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="name">@lang('view_pages.name') <span class="text-danger">*</span></label>
                                            <input class="form-control" type="text" id="name" name="name"
                                                value="{{ old('name') }}" required=""
                                                placeholder="@lang('view_pages.enter_name')">
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="gender">@lang('view_pages.gender') <span class="text-danger">*</span></label>
                                            <select name="gender" id="gender" class="form-control" required>
                                                <option value="">@lang('view_pages.select_gender')</option>
                                                <option value='male' {{ old('gender') == 'male' ? 'selected' : '' }}>
                                                    @lang('view_pages.male')</option>
                                                <option value='female' {{ old('gender') == 'female' ? 'selected' : '' }}>
                                                    @lang('view_pages.female')</option>
                                                <option value='others' {{ old('gender') == 'others' ? 'selected' : '' }}>
                                                    @lang('view_pages.others')</option>
                                            </select>
                                            <span class="text-danger">{{ $errors->first('gender') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="email">@lang('view_pages.email') <span class="text-danger">*</span></label>
                                            <input class="form-control" type="email" id="email" name="email"
                                                value="{{ old('email') }}" required=""
                                                placeholder="@lang('view_pages.enter_email')">
                                            <span class="text-danger">{{ $errors->first('email') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="country">@lang('view_pages.select_country') <span class="text-danger">*</span></label>
                                            <select name="country" id="country" class="form-control" required>
                                                <option value="">@lang('view_pages.select_country')</option>
                                                @foreach ($countries as $key => $country)
                                                    <option value="{{ $country->id }}"
                                                        {{ old('country') == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger">{{ $errors->first('country') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="mobile">@lang('view_pages.mobile') <span class="text-danger">*</span></label>
                                            <input class="form-control" type="text" id="mobile" name="mobile"
                                                value="{{ old('mobile') }}" required=""
                                                placeholder="@lang('view_pages.enter_mobile')">
                                            <span class="text-danger">{{ $errors->first('mobile') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="cpf">CPF <span class="text-danger">*</span></label>
                                            <input class="form-control" type="text" id="cpf" name="cpf"
                                                value="{{ old('cpf') }}" required=""
                                                placeholder="Digite o CPF">
                                            <span class="text-danger">{{ $errors->first('cpf') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="date_of_birth">Data de Nascimento <span class="text-danger">*</span></label>
                                            <input class="form-control" type="date" id="date_of_birth" name="date_of_birth"
                                                value="{{ old('date_of_birth') }}" required=""
                                                placeholder="Digite a data de nascimento">
                                            <span class="text-danger">{{ $errors->first('date_of_birth') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="password">@lang('view_pages.password') <span class="text-danger">*</span></label>
                                            <input class="form-control" type="password" id="password" name="password"
                                                required="" placeholder="@lang('view_pages.enter_password')">
                                            <span class="text-danger">{{ $errors->first('password') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="password_confirmation">@lang('view_pages.confirm_password') <span class="text-danger">*</span></label>
                                            <input class="form-control" type="password" id="password_confirmation"
                                                name="password_confirmation" required=""
                                                placeholder="@lang('view_pages.enter_confirm_password')">
                                            <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="profile_picture">@lang('view_pages.profile')</label><br>
                                            <img id="blah" src="#" alt=" " style="display:none;"><br>
                                            <input type="file" id="profile" onchange="readURL(this)" name="profile_picture"
                                                style="display:none;">
                                            <button class="btn btn-primary btn-sm" type="button"
                                                onclick="$('#profile').click()" id="upload">@lang('view_pages.browse')</button>
                                            <button class="btn btn-danger btn-sm" type="button" id="remove_img"
                                                style="display: none;">@lang('view_pages.remove')</button><br>
                                            <span class="text-danger">{{ $errors->first('profile_picture') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-12">
                                        <button class="btn btn-primary btn-sm pull-right m-5" type="submit">
                                            @lang('view_pages.save')
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#blah').attr('src', e.target.result).show();
                    $('#remove_img').show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $('#remove_img').click(function() {
            $('#blah').attr('src', '#').hide();
            $('#profile').val('');
            $(this).hide();
        });
    </script>
@endsection