@extends('layouts.app')

@section('content')
<section id="dom">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-wrap">
                        <h4 class="card-title">Challenges</h4>
                        <p class="card-text">Here you can see the list of existing challenges entries.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-block table-responsive">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from" class="label-control">From Date</label>
                                    <input type="date" class="form-control" id='date_from' value="">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to" class="label-control">To Date</label>
                                    <input type="date"  class="form-control" id='date_to' value="">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <table class="table table-striped table-bordered" id="dTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Start Time</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('afterScript')
<script>
    var table = $('#dTable').DataTable({
        processing: true,
        serverSide: true,
        ajax:
        {
            url: '{{ route("challenges.getList") }}',
            type: 'GET',
            dataType: 'JSON',
            data:function(data){
                data.date_from= $('#date_from').val();
                data.date_to= $('#date_to').val();
            },
            error: function (reason) {
                return true;
            }
        },
        columns: [
            { data: 'serial'},
            { data: 'title' },
            { data: 'start_time' },
            { data: 'user.name' },
            { data: 'actions', render:function (data, type, full, meta) {
                                    return `<a href="/challenges/${full.id}" class="showStatus info p-0 mr-2 success" title="View">
                                                <i class="ft-eye font-medium-3"></i>
                                            </a>
                                            <a href="/challenges/${full.id}/edit/" class="edit success p-0 mr-2" title="Edit">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>`;
                                }
            }
        ],
        columnDefs: [
            { width: "10%", "targets": [-1, 0] },
            { orderable: false, targets: [-2, -1] }
        ],
    });
    $('#date_from, #date_to').change(function(){
        $('#dTable').DataTable().ajax.reload();
    })

    // window.onload = function () {
    //     $(window).bind('pagehide', function(e){
    //         if (!e.persisted){
    //             // Cancel the event
    //             e.preventDefault();
    //             //     e=null; // i.e; if form state change show warning box, else don't show it.
    //             //     $.ajax({
    //             //         url:  '/enter_log/unload/' +  $('#lastid').val(),
    //             //         type: 'GET',
    //             //         async: false,
    //             //         success: function(data) {
    //             //             console.log(data);
    //             //         },
    //             //         error: function(data) {
    //             //             console.log(data);
    //             //         }
    //             //     });
    //             return $.ajax({
    //                 url:  '/enter_log/unload/' +  $('#lastid').val(),
    //                 type: 'GET',
    //                 cache: false,
    //                 async: false,
	//                 headers: { "cache-control": "no-cache" },
    //                 success: function(data) {
    //                     console.log(data);
    //                 },
    //                 error: function(data) {
    //                     console.log(data);
    //                 }
    //             }).then(function(data) {
    //                 return false;
    //             });
    //         }
    //     });
    // };
</script>
@endsection
