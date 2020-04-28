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
                <div class="card-body collapse show">
                    <div class="card-block card-dashboard table-responsive">
                        <table class="table table-striped table-bordered" id="dTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
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
        ajax: '{{ route("challenges.getList") }}',
        columns: [
            { data: 'serial'},
            { data: 'title' },
            { data: 'actions', render:function (data, type, full, meta) {
                                    return `<a href="/Admin/users/${full.id}" class="showStatus info p-0 mr-2 success" title="View">
                                                <i class="ft-eye font-medium-3"></i>
                                            </a>
                                            <a href="/Admin/users/${full.id}/edit/" class="edit success p-0 mr-2" title="Edit">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>`;
                                }
            }
        ],
        columnDefs: [
            { width: "10%", "targets": [-1, 0] },
            { orderable: false, targets: [-1] }
        ],
    });
</script>
@endsection
