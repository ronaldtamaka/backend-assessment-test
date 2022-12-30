@extends('admin/utama/index')

@section('dashcontent')
<section class="content" style="margin-top:30px;">
    <input name="csrfToken" value="{{ csrf_token() }}" type="hidden">

    <div class="modal bd-example-modal-lg fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <form method="post" action="{{ url('debit-card-transactions') }}">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Transaction Debit Card</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        {{ csrf_field() }}


                        <div class="col-md-12">
                            <label> Debit Card Id : </label>
                        </div>
                        <div class="col-md-12">
                            <input type="number" name="debit_card_id" id="debit_card_id" class="form-control" />
                            <!-- <input type="text" name="inp_nama_kategori" class="form-control" placeholder="Input Nama Kategori" required="required" /> -->
                        </div>


                        <div class="col-md-12">
                            <label> Amount : </label>
                        </div>
                        <div class="col-md-12">
                            <input type="number" name="amount" id="amount" class="form-control" />
                            <!-- <input type="text" name="inp_nama_kategori" class="form-control" placeholder="Input Nama Kategori" required="required" /> -->
                        </div>

                        <div class="col-md-12">
                            <label> Currency Code : </label>
                        </div>
                        <div class="col-md-12">
                            <input type="text" name="currency_code" id="currency_code" class="form-control" />
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
                        <h3 class="card-title">Transaksi DebitCard Table</h3>

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
                        <div class="col-md-12" style="padding:20px;">
                            <label> Find Transactions By Debit Card Id</label>
                            <div class="row">
                                <div class="col-md-10">
                                    <input type="text" name="sr_debit_card" id="sr_debit_card" placeholder="Input Debit Card ID" class="form-control" />
                                </div>
                                <div class="col-md-2">
                                    <Button class="btn btn-primary form-control" onclick="fetch_data()"> Search</Button>
                                </div>
                            </div>
                        </div>
                        <table id="example2" class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Currency Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="2" align="center"> - No Data Shown - </td>
                                </tr>
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

    function fetch_data() {
        let debit_id = $("#sr_debit_card").val();

        $('#example2').DataTable({
            "ajax": {
                "url": url_fetch + "/debit-card-transactions?debit_card_id=" + debit_id,
                "dataSrc": ""
            },
            columns: [{
                    "data": "amount"
                },
                {
                    "data": "currency_code"
                }
            ]
        });

    }
</script>
@endsection