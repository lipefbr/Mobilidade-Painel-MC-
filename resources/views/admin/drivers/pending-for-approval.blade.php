@extends('admin.layouts.app')

@section('title', 'Motoristas Pendentes de Aprovação')

@section('content')
    <style>
        .demo-radio-button label {
            min-width: 100px;
            margin: 0 0 5px 50px;
        }
    </style>
    <!-- Start Page content -->
    <section class="content">
        <div class="row">
            <div class="col-12">
 tela            <div class="box">
                    <div class="box-header with-border">
                        <div class="row text-right">
                            <div class="col-4 col-md-3">
                                <div class="form-group">
                                    <div class="controls">
                                        <input type="text" id="search_keyword" name="search" class="form-control"
                                            placeholder="@lang('view_pages.enter_keyword')">
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 col-md-3">
                                <div class="form-group">
                                    <div class="controls">
                                        <input type="text" id="search_cpf" name="cpf" class="form-control"
                                            placeholder="Buscar por CPF">
                                    </div>
                                </div>
                            </div>
                            <div class="col-2 col-md-1 text-left">
                                <button id="search" class="btn btn-success btn-outline btn-sm py-2" type="submit">
                                    @lang('view_pages.search')
                                </button>
                            </div>
                            <div class="col-5 col-md-1 text-left">
                                <button class="btn btn-outline btn-sm btn-danger py-2" type="button" data-toggle="modal"
                                    data-target="#modal-default">
                                    @lang('view_pages.filter_drivers')
                                </button>
                            </div>
                            <div class="col-12 col-md-7 text-right">
                                @if(auth()->user()->can('add-drivers'))
                                    <a href="{{ url('drivers/create') }}" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-plus-circle mr-2"></i>@lang('view_pages.add_driver')
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Modal -->
                        <div class="modal fade" id="modal-default">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">@lang('view_pages.filter_drivers')</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body text-left">
                                        <h4>@lang('view_pages.online_status')</h4>
                                        <div class="demo-radio-button">
                                            <input name="available" type="radio" id="online" data-val="1"
                                                class="with-gap radio-col-green">
                                            <label for="online">@lang('view_pages.online')</label>
                                            <input name="available" type="radio" id="offline" data-val="0"
                                                class="with-gap radio-col-grey">
                                            <label for="offline">@lang('view_pages.offline')</label>
                                        </div>
                                        <h4>@lang('view_pages.select_area')</h4>
                                        <div class="form-group">
                                            <select name="service_location_id" id="service_location_id" class="form-control">
                                                <option value="all" selected>@lang('view_pages.all')</option>
                                                @foreach($services as $key => $service)
                                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" data-dismiss="modal"
                                            class="btn btn-success btn-sm float-right filter">@lang('view_pages.apply_filters')</button>
                                        <button type="button" data-dismiss="modal"
                                            class="btn btn-danger btn-sm resetfilter float-right mr-2">@lang('view_pages.reset_filters')</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="js-drivers-partial-target">
                        <include-fragment src="fetch/approval-pending-drivers">
                            <span style="text-align: center; font-weight: bold;">@lang('view_pages.loading')</span>
                        </include-fragment>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('assets/js/fetchdata.min.js') }}"></script>
        <script>
            $(document).ready(function() {
                // Função para formatar CPF
                function formatCpf(cpf) {
                    cpf = cpf.replace(/\D/g, '');
                    if (cpf.length > 11) {
                        cpf = cpf.substring(0, 11);
                    }
                    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }

                // Formatar CPF enquanto o usuário digita
                $('#search_cpf').on('input', function() {
                    $(this).val(formatCpf($(this).val()));
                });

                // Função para validar CPF (básica)
                function isValidCPF(cpf) {
                    cpf = cpf.replace(/\D/g, ''); // Remove caracteres não numéricos
                    return cpf.length === 11; // CPF deve ter 11 dígitos
                }

                // Evento de busca
                $('#search').on('click', function(e) {
                    e.preventDefault();
                    var search_keyword = $('#search_keyword').val().trim();
                    var search_cpf = $('#search_cpf').val().trim();

                    // Validar CPF
                    if (search_cpf && !isValidCPF(search_cpf)) {
                        alert('Por favor, insira um CPF válido (11 dígitos ou formato XXX.XXX.XXX-XX).');
                        return;
                    }

                    var url = 'fetch/approval-pending-drivers';
                    var params = [];

                    if (search_cpf) {
                        params.push('cpf=' + encodeURIComponent(search_cpf));
                    }
                    if (search_keyword) {
                        params.push('search=' + encodeURIComponent(search_keyword));
                    }

                    if (params.length > 0) {
                        url += '?' + params.join('&');
                    }

                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro na requisição: ' + response.status);
                            }
                            return response.text();
                        })
                        .then(html => {
                            document.querySelector('#js-drivers-partial-target').innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Erro na busca:', error);
                            alert('Ocorreu um erro ao realizar a busca. Tente novamente.');
                        });
                });

                // Paginação
                $('body').on('click', '.pagination a', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('href');
                    $.get(url, function(data) {
                        $('#js-drivers-partial-target').html(data);
                    });
                });

                // Filtros
                $('.filter, .resetfilter').on('click', function() {
                    let filterColumn = ['available', 'area'];
                    let className = $(this);
                    var query = '';

                    $.each(filterColumn, function(index, value) {
                        if (className.hasClass('resetfilter')) {
                            $('input[name="' + value + '"]').prop('checked', false);
                            if (value == 'area') $('#service_location_id').val('all');
                            query = '';
                        } else {
                            if ($('input[name="' + value + '"]:checked').attr('id') != undefined) {
                                var activeVal = $('input[name="' + value + '"]:checked').attr('data-val');
                                query += value + '=' + activeVal + '&';
                            } else if (value == 'area') {
                                var area = $('#service_location_id').val();
                                query += 'area=' + area + '&';
                            }
                        }
                    });

                    fetch('fetch/approval-pending-drivers?' + query)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro na requisição: ' + response.status);
                            }
                            return response.text();
                        })
                        .then(html => {
                            document.querySelector('#js-drivers-partial-target').innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Erro ao aplicar filtros:', error);
                            alert('Ocorreu um erro ao aplicar os filtros. Tente novamente.');
                        });
                });

                // Exclusão de motorista
                $(document).on('click', '.sweet-delete', function(e) {
                    e.preventDefault();
                    let url = $(this).attr('data-url');
                    swal({
                        title: "Tem certeza que deseja excluir?",
                        type: "error",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Excluir",
                        cancelButtonText: "Não, manter",
                        closeOnConfirm: false,
                        closeOnCancel: true
                    }, function(isConfirm) {
                        if (isConfirm) {
                            $.ajax({
                                url: url,
                                cache: false,
                                success: function(res) {
                                    fetch('fetch/approval-pending-drivers')
                                        .then(response => response.text())
                                        .then(html => {
                                            document.querySelector('#js-drivers-partial-target').innerHTML = html;
                                        });
                                    $.toast({
                                        heading: '',
                                        text: res,
                                        position: 'top-right',
                                        loaderBg: '#ff6849',
                                        icon: 'success',
                                        hideAfter: 5000,
                                        stack: 1
                                    });
                                    swal.close();
                                },
                                error: function() {
                                    alert('Erro ao excluir motorista.');
                                }
                            });
                        }
                    });
                });

                // Declinar motorista
                $(document).on('click', '.decline', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var inpVal = button.attr('data-reason') || '';
                    var driver_id = button.attr('data-id');
                    var redirect = button.attr('href');

                    swal({
                        title: "Motivo da Recusa",
                        text: "Digite o motivo da recusa",
                        type: "input",
                        showCancelButton: true,
                        closeOnConfirm: false,
                        confirmButtonText: 'Recusar',
                        cancelButtonText: 'Fechar',
                        confirmButtonColor: '#fc4b6c',
                        animation: "slide-from-top",
                        inputPlaceholder: "Digite o motivo da recusa",
                        inputValue: inpVal
                    }, function(inputValue) {
                        if (inputValue === false) return false;
                        if (inputValue === "") {
                            swal.showInputError("O motivo é obrigatório!");
                            return false;
                        }

                        $.ajax({
                            url: '{{ route('UpdateDriverDeclineReason') }}',
                            data: {
                                "_token": "{{ csrf_token() }}",
                                'reason': inputValue,
                                'id': driver_id
                            },
                            method: 'post',
                            success: function(res) {
                                if (res == 'success') {
                                    window.location.href = redirect;
                                    swal.close();
                                }
                            },
                            error: function() {
                                alert('Erro ao atualizar motivo de recusa.');
                            }
                        });
                    });
                });
            });
        </script>
    @endsection
