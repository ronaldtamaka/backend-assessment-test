@extends('admin/utama/index')

@section('dashcontent')
<section class="content" style="margin-top:30px;">
    <input name="csrfToken" value="{{ csrf_token() }}" type="hidden">

    <div class="modal bd-example-modal-lg fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <form method="post" action="{{ url('debit-cards') }}">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Debit Card</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        {{ csrf_field() }}
                        <div class="col-md-12">
                            <label> Debit Card Type : </label>
                        </div>
                        <div class="col-md-12">
                            <select class="form-control" name="type" id="type_select">
                                <option value="">Pilih Type Debit Card</option>
                                @php
                                $i = 0
                                @endphp
                                @foreach($type_debit as $values)
                                @php
                                $i++
                                @endphp
                                <option value="{{ $i }}">{{ $values }}</option>
                                @endforeach
                            </select>
                            <!-- <input type="text" name="inp_nama_kategori" class="form-control" placeholder="Input Nama Kategori" required="required" /> -->
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

    <div class="modal bd-example-modal-lg fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Debit Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    {{ csrf_field() }}
                    <div class="col-md-12">
                        <label> Status Aktif : </label>
                    </div>
                    <div class="col-md-12">
                        <select class="form-control" name="type" id="edit_type_select">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                        <!-- <input type="text" name="inp_nama_kategori" class="form-control" placeholder="Input Nama Kategori" required="required" /> -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" onclick="edit_data()" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>

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
                        <h3 class="card-title">DebitCard Table</h3>

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
                        <table id="example2" class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Debit Card Number</th>
                                    <th>Type</th>
                                    <th>Expiration Date</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

    </div>
</section>
@endsection

@section("footers_js")
<script type="text/javascript">
    let url_fetch = "<?php echo url('/'); ?>";
    let selectedId = "";
    let token = $('input[name="csrfToken"]').attr('value');

    function delete_debit(id) {

        $.ajax({
            url: url_fetch + "/debit-cards/" + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-Token': token
            },
            dataType: "json",
            success: function(result) {
                alert("data deleted");
                window.location = url_fetch + "/list-debit-cards";
                // Do something with the result
            }
        });
    }

    function show_edit_modal(id) {
        selectedId = id;
        $.ajax({
            url: url_fetch + "/debit-cards/" + id,
            type: 'GET',
            dataType: "json",
            success: function(result) {
                $("#type_select").val(result.is_active);

                $("#editModal").modal("show");
                // Do something with the result
            }
        });
    }

    function edit_data() {
        let typeselect = $("#edit_type_select").val();

        $.ajax({
            url: url_fetch + "/debit-cards/" + selectedId,
            type: 'PUT',
            data: "is_active=" + typeselect,
            headers: {
                'X-CSRF-Token': token
            },
            dataType: "json",
            success: function(result) {
                alert("data updated");
                window.location = url_fetch + "/list-debit-cards";
                // Do something with the result
            }
        });

        return false;
    }

    $('#example2').DataTable({
        "ajax": {
            "url": url_fetch + "/debit-cards",
            "dataSrc": ""
        },
        columns: [{
                "data": "id"
            },
            {
                "data": "number"
            },
            {
                "data": "type"
            },
            {
                "data": "expiration_date"
            },
            {
                // The `data` parameter refers to the data for the cell (defined by the
                // `data` option, which defaults to the column being worked with, in
                // this case `data: 0`.
                render: function(data, type, row) {
                    let delUrl = url_fetch + "/debit-cards";

                    return "<div> <a href='#' onClick='show_edit_modal(" + row.id + ")'><i class='fas fa-edit'></i> Edit </a> &nbsp; <a href='#' onClick='delete_debit(" + row.id + ");'><i class='fas fa-trash'></i> Delete</a> </div>";
                },
                targets: 0,
            },
        ]
    });
</script>
@endsection