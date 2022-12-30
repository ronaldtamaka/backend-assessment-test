@extends('admin/utama/index')

@section('dashcontent')
<section class="content" style="margin-top:30px;">

    <div class="modal bd-example-modal-lg fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <form method="post" action="{{ url('dashboard/kategori/add') }}">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add New Kategori</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        {{ csrf_field() }}
                        <div class="col-md-12">
                            <label> Nama Kategori : </label>
                        </div>
                        <div class="col-md-12">
                            <input type="text" name="inp_nama_kategori" class="form-control" placeholder="Input Nama Kategori" required="required" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="container-fluid">

        <Button class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" style="width:200px; float:right; margin:20px 0; clear:both;">
            <i class="fas fa-plus"></i> Tambah Data
        </Button>

        <br clear="all" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Blog Kategori Table</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 150px;">
                                <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['cats'] as $rows)
                                <tr>
                                    <td>{{ $rows->id }}</td>
                                    <td>{{ $rows->name }}</td>
                                    <td><span class="tag tag-success">Active</span></td>
                                    <td>
                                        <a href="#"><i class="fas fa-edit"></i> Edit </a>&nbsp;
                                        <a href="#"><i class="fas fa-trash"></i> Delete </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <div>
            {{ $data['cats']->links() }}
        </div>

    </div>
</section>
@endsection