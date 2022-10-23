<?php
include 'head.php';

$id = $_GET['id'];
$dts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM surat_keluar WHERE id_keluar = $id "));


?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Surat
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Persuratan</a></li>
            <li class="active">Data Surat Keluar</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Data Surat Keluar</h3>

                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <form class="form-horizontal form-label-left" method="post" enctype="multipart/form-data">
                            <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">No. Surat<span class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="text" name="no_surat" class="form-control col-md-7 col-xs-12" value="<?= $dts['no_surat'] ?>" required="required">
                                </div>
                            </div>
                            <!-- <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Jenis Surat<span
                                        class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <select name="jenis_surat" class="form-control col-md-7 col-xs-12"
                                        required="required">
                                        <option value=""> -pilih jenis- </option>
                                        <?php foreach ($jns as $dtjn) : ?>
                                        <option value="<?= $dtjn['kode']; ?>">
                                            <?= $dtjn['kode'] . ' - ' . $dtjn['nama']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div> -->
                            <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Tanggal Surat<span class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="date" name="tanggal_kirim" class="form-control has-feedback-left" value="<?= $dts['tanggal_kirim'] ?>" id="tanggal">
                                </div>
                            </div>
                            <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Tujuan<span class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="text" name="tujuan" class="form-control col-md-7 col-xs-12" required="required" value="<?= $dts['tujuan'] ?>">
                                </div>
                            </div>
                            <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Perihal<span class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <textarea name="isi_ringkas" class="form-control col-md-7 col-xs-12" required="required"> <?= $dts['isi_ringkas'] ?></textarea>
                                </div>
                            </div>
                            <div class="item form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Publish<span class="required">&nbsp; :</span></label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <input type="radio" name="pub" id="" value="YA" <?= $dts['publish'] === 'YA' ? 'checked' : '' ?>> YA
                                    <input type="radio" name="pub" id="" value="TIDAK" <?= $dts['publish'] === 'TIDAK' ? 'checked' : '' ?>> TIDAK
                                </div>
                            </div>
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-3">
                                    <button type="reset" class="btn btn-default">Reset</button>
                                    <input type="submit" class="btn btn-success" value="Simpan" name="submit">
                                </div>
                            </div>
                        </form>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->

            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
</div><!-- /.content-wrapper -->
<!-- DataTables -->
<link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
<!-- jQuery 2.1.4 -->
<script src="plugins/jQuery/jQuery-2.1.4.min.js"></script>
<!-- Bootstrap 3.3.5 -->
<script src="bootstrap/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables/dataTables.bootstrap.min.js"></script>
<script>
    $(function() {
        $("#example1_bst").DataTable();
        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false
        });
    });
</script>
<?php
include 'foot.php';
include "phpqrcode/qrlib.php";

if (isset($_REQUEST['submit'])) {
    $no_surat        = $_POST['no_surat'];
    $tanggal_kirim    = $_POST['tanggal_kirim'];
    $tujuan         = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['tujuan']));
    $isi_ringkas    = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['isi_ringkas']));
    $pub    = $_POST['pub'];

    // echo QRcode::png($kode);

    
        $query         = "UPDATE surat_keluar SET no_surat = '$no_surat', tanggal_kirim = '$tanggal_kirim', tujuan = '$tujuan', isi_ringkas = '$isi_ringkas', publish = '$pub' WHERE id_keluar = $id ";
        $sql        = mysqli_query($conn, $query);
        if ($sql) {
            echo '<script>
    				window.alert("Data berhasil di simpan");
    				window.location.href="keluar.php";
    			  </script>';
        } else {
            echo '<script>
    				window.alert("Data gagal di simpan");
    			  </script>';
        }
    
}

?>