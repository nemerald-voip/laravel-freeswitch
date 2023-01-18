@extends('layouts.horizontal', ["page_title"=> "Fax Server"])

@section('content')
<!-- Start Content-->
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('faxes.index') }}">Fax</a></li>
                        @if($fax->exists)
                            <li class="breadcrumb-item active">Edit Fax Server</li>
                        @else
                            <li class="breadcrumb-item active">Create New Fax Server</li>
                        @endif
                    </ol>
                </div>
                @if($fax->exists)
                    <h4 class="page-title">Edit Fax Server ({{ $fax->fax_name ?? ''}})</h4>
                @else
                    <h4 class="page-title">Create New Fax Server</h4>
                @endif

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card mt-3">
                <div class="card-body">

                    <div class="tab-content">
                            
                        <!-- Body Content-->
                            <div class="row">
                                <div class="col-lg-12">

                                    @if ($fax->exists)
                                        <form method="POST" id="fax_form" action="{{ route('faxes.update',$fax) }}">
                                        @method('put')
                                    @else
                                        <form method="POST" id="fax_form" action="{{ route('faxes.store') }}">
                                    @endif
                                      
                                    @csrf

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_name" class="form-label">Name <span class="text-danger">*</span></label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_name ?? ''}}" 
                                                        placeholder="Enter fax name" id="fax_name" name="fax_name" />
                                                    <div class="text-danger error_message fax_name_err"></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_extension" class="form-label">Extension <span class="text-danger">*</span></label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_extension ?? ''}}" 
                                                        placeholder="Enter fax extension" id="fax_extension" name="fax_extension" />
                                                    <div class="text-danger error_message fax_extension_err"></div>
                                                </div>
                                            </div>

                                            {{-- <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="accountcode" class="form-label">Account Code </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->accountcode ?? $domain}}" 
                                                        placeholder="Enter fax name" id="accountcode" name="accountcode" />
                                                    <div class="text-danger error_message accountcode_err"></div>
                                                </div>
                                            </div> --}}
                                        </div> <!-- end row -->


                                        
                                        <div class="row">
                                            {{-- <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="fax_destination_number" class="form-label">Destination Number </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_destination_number ?? ''}}" 
                                                        placeholder="Enter fax name" id="fax_destination_number" name="fax_destination_number" />
                                                    <div class="text-danger error_message fax_destination_number_err"></div>
                                                </div>
                                            </div> --}}

                                            {{-- <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="fax_prefix" class="form-label">Prefix </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_prefix ?? ''}}" 
                                                        placeholder="Enter fax name" id="fax_prefix" name="fax_prefix" />
                                                    <div class="text-danger error_message fax_prefix_err"></div>
                                                </div>
                                            </div> --}}

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_email" class="form-label">Email </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_email ?? ''}}" 
                                                        placeholder="Enter email" id="fax_email" name="fax_email" />
                                                    <div class="text-danger error_message fax_email_err"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_caller_id_name" class="form-label">Caller ID Name </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_caller_id_name ?? ''}}" 
                                                        placeholder="Enter Caller ID name" id="fax_caller_id_name" name="fax_caller_id_name" />
                                                    <div class="text-danger error_message fax_caller_id_name_err"></div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->


                                        
                                        
                                        <div class="row">

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_caller_id_number" class="form-label">Caller ID Number </label>
                                                    {{-- <input class="form-control"  type="text" value="{{ $fax->fax_caller_id_number ?? ''}}" 
                                                        placeholder="Enter Caller ID Number" id="fax_caller_id_number" name="fax_caller_id_number" /> --}}

                                                        <select data-toggle="select2" title="Fax Caller ID" name="fax_caller_id_number">
                                                            <option value="">Main Company Number</option>
                                                            @foreach ($destinations as $destination)
                                                                <option value="{{ phone($destination->destination_number, "US")->formatE164() }}"
                                                                    @if (($fax->fax_caller_id_number &&
                                                                        phone($fax->fax_caller_id_number, "US")->formatE164() == phone($destination->destination_number, "US")->formatE164()))
                                                                        selected 
                                                                    @endif>
                                                                    {{ phone($destination->destination_number,"US",$national_phone_number_format) }}
                                                                </option>
                                                            @endforeach
                                                        </select>    

                                                    <div class="text-danger error_message fax_caller_id_number_err"></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_forward_number" class="form-label">Forward Number </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_forward_number ?? ''}}" 
                                                        placeholder="Enter Forward Number" id="fax_forward_number" name="fax_forward_number" />
                                                    <div class="text-danger error_message fax_forward_number_err"></div>
                                                </div>
                                            </div>
                                        </div> <!-- end row -->


                                        
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_toll_allow" class="form-label">Toll Allowed </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_toll_allow ?? ''}}" 
                                                        placeholder="Enter Toll Allowed" id="fax_toll_allow" name="fax_toll_allow" />
                                                    <div class="text-danger error_message fax_toll_allow_err"></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="fax_send_channels" class="form-label">Number of channels </label>
                                                    <input class="form-control"  type="text" value="{{ $fax->fax_send_channels ?? 10}}" 
                                                        placeholder="Enter Number of channels" id="fax_send_channels" name="fax_send_channels" />
                                                    <div class="text-danger error_message fax_send_channels_err"></div>
                                                </div>
                                            </div>

                                        </div> <!-- end row -->

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="fax_description" class="form-label">Description </label>
                                                    <textarea class="form-control" type="text" placeholder="" id="fax_description" name="fax_description"
                                                        />{{ $fax->fax_description }}</textarea>
                                                    <div class="text-danger error_message fax_description_err"></div>
                                                </div>
                                            </div>
                                        </div>
                                      
                                        <div class="row">

                                            <select class="select2 emailListSelect2 select2-multiple" data-toggle="select2" multiple="multiple" title="EmailList" name="email_list[]">
                                                @foreach ($allowed_emails as $email)
                                                    <option selected value="{{ $email->email }}">
                                                        {{ $email->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <select class="select2 domainListSelect2 select2-multiple" data-toggle="select2" multiple="multiple" title="DomainList" name="domain_list[]">
                                                @foreach ($allowed_domain_names as $domain_name)
                                                    <option selected value="{{ $domain_name->domain }}">
                                                        {{ $domain_name->domain }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                            
                                            <div class="row mt-4">
                                                <div class="col-sm-12">
                                                    <div class="text-sm-end">
                                                    <input type="hidden" name="fax_uuid" value="{{ $fax->fax_uuid}}">
                                                      <a href="{{ Route('faxes.index') }}" class="btn btn-light">Close</a>
                                                       <button id="submitFormButton" class="btn btn-success" type="submit">Save </button>
                                                    </div>
                                                </div> <!-- end col -->
                                            </div>
    

                                    </form>
                                </div>
                            </div> <!-- end row-->

                    </div>




                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    <!-- end row-->

    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Additional users allowed to use email-to-fax</h4>
                </div>

                <div class="card-body pt-0">
                    <div class="row align-items-center">
                        
                        <form id="emailAdrressForm">
                            <div class="alert alert-info" role="alert">
                                <strong>Info - </strong> By default, only email addresses entered on Extensions and User pages are authorized to use email-to-fax. You may enter any additional trusted email addresses below.
                            </div>
                            <div class="row">
                                <label for="emailAddress" class="form-label">Add a new trusted email address</label>
                                <div class="mb-3 col-6">
                                    <input type="email" id="emailAddress" name="example-email" class="form-control" placeholder="Email">
                                </div>
                                <div class="mb-3 col-3">
                                    <button type="submit" class="btn btn-primary ms-2">Add</button>

                                </div>
                            </div>
                        </form>
                        
                        <div id="listOfEmails">
                            @foreach ($allowed_emails as $email)
                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-primary rounded-pill btn-sm mb-2 emailButton">{{ $email->email }}<i class="mdi mdi-close ms-2"></i></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Domains allowed to use email-to-fax</h4>
                </div>

                <div class="card-body pt-0">
                    <div class="row align-items-center">
                        
                        <form id="domainNamesForm">
                            <div class="alert alert-info" role="alert">
                                <strong>Info - </strong> This feature allows accepting faxes from specific email domains. All of the messages sent from addresses on those domains will be allowed. You may enter multiple domains.
                            </div>
                            <div class="row">
                                <label for="domainName" class="form-label">Add a new trusted domain name (example.com)</label>
                                <div class="mb-3 col-6">
                                    <input type="text" id="domainName" class="form-control" placeholder="Domain">
                                </div>
                                <div class="mb-3 col-3">
                                    <button type="submit" class="btn btn-primary ms-2">Add</button>

                                </div>
                            </div>
                        </form>
                        
                        <div id="listOfDomains">
                            @foreach ($allowed_domain_names as $domain_name)
                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-primary rounded-pill btn-sm mb-2 domainNameButton">{{ $domain_name->domain }}<i class="mdi mdi-close ms-2"></i></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> <!-- container -->

@endsection


@push('scripts')

<script>
    $(document).ready(function() {
    });

    $("#listOfEmails").on("click", ".emailButton", function(){
            $(".emailListSelect2 option[value='" + $(this).text() + "']").remove();
            $(this).remove();
    });

    $("#listOfDomains").on("click", ".domainNameButton", function(){
            $(".domainListSelect2 option[value='" + $(this).text() + "']").remove();
            $(this).remove();
    });

    $('#emailAdrressForm').submit(function(e){
        var emailAddress = $(this).find('#emailAddress').val();
        e.preventDefault();

        if (!$('.emailListSelect2').find("option[value='" + emailAddress + "']").length) {
            var newOption = new Option(emailAddress, emailAddress, true, true);
            $('.emailListSelect2').append(newOption).trigger('change');
            $('#listOfEmails').append('<div class="row"><div class="col-12"><button type="button" class="btn btn-outline-primary rounded-pill btn-sm mb-2 emailButton">' + emailAddress + '<i class="mdi mdi-close ms-2"></i></button></div></div>');

        }
    });

    
    $('#domainNamesForm').submit(function(e){
        var domainName = $(this).find('#domainName').val();
        e.preventDefault();

        if (!$('.domainListSelect2').find("option[value='" + domainName + "']").length) {
            var newOption = new Option(domainName, domainName, true, true);
            $('.domainListSelect2').append(newOption).trigger('change');
            $('#listOfDomains').append('<div class="row"><div class="col-12"><button type="button" class="btn btn-outline-primary rounded-pill btn-sm mb-2 domainNameButton">' + domainName + '<i class="mdi mdi-close ms-2"></i></button></div></div>');

        }
    });
    
    $('#submitFormButton').on('click', function(e) {
        e.preventDefault();
        $('.loading').show();
        
        //Reset error messages
        $('.error_message').text("");

        $.ajax({
            type : "POST",
            url: $('#fax_form').attr('action'),
            cache: false,
            data: $("#fax_form").serialize(),
        })
        .done(function(response) {
            // console.log(response);
            $('.loading').hide();

            if (response.error){
                printErrorMsg(response.error);

            } else {
                $.NotificationApp.send("Success",response.message,"top-right","#10c469","success");
                setTimeout(function (){
                    @if(!$fax->exists)
                    window.location.href = response.redirect_url;
                    @endif
                }, 1000);

            }
        })
        .fail(function (jqXHR, testStatus, error) {
            // console.log(error);
            $('.loading').hide();
            printErrorMsg(error);

        });
    });

</script>
@endpush