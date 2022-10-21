<?php include 'head.php'; ?>
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
                        <a href="keluar_add.php" class="btn btn-success btn-flat btn-sm pull-right"><i class="fa fa-plus"></i> Tambah
                            Surat Keluar</a>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="example1_bst" class="table table-bordered table-striped">
                                <thead>
                                    <tr style="font-size: 13px;">
                                        <th width="1" style="vertical-align: middle;">No</th>
                                        <th style="vertical-align: middle;">
                                            <center>Nomor Surat</center>
                                        </th>
                                        <th style="vertical-align: middle;">
                                            <center>Perihal</center>
                                        </th>
                                        <th style="vertical-align: middle;">
                                            <center>Tujuan</center>
                                        </th>
                                        <th style="vertical-align: middle;">
                                            <center> Tanggal Surat</center>
                                        </th>
                                        <th style="vertical-align: middle;">
                                            <center>Action</center>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php
                                        $no = 1;
                                        $query  = "SELECT * FROM surat_keluar ORDER BY id_keluar DESC";
                                        $sql  = mysqli_query($conn, $query);
                                        while ($data = mysqli_fetch_array($sql)) {
                                        ?>
                                            <td width="1" style="vertical-align: middle;"><?= $no++; ?></td>
                                            <td style="vertical-align: middle;"><?= $data['no_surat'] ?></td>
                                            <td style="vertical-align: middle;"><?= $data['isi_ringkas'] ?></td>
                                            <td style="vertical-align: middle;"><?= $data['tujuan'] ?></td>
                                            <td style="vertical-align: middle;"><?= ($data['tanggal_kirim']) ?></td>
                                            <td>
                                                <div class="btn-group btn-flat">
                                                    <?php if ($data['file'] != '-') { ?>
                                                        <a href="upload/surat_keluar/<?= $data['file'] ?>" class="btn btn-primary btn-xs" title="Download Berkas"><i class="fa fa-download"></i></a>
                                                    <?php } else { ?>
                                                        <button class="btn btn-primary btn-xs" title="Download Berkas" disabled><i class="fa fa-download"></i></button>
                                                    <?php } ?>
                                                    <button data-toggle="modal" data-target=".bs-example-modal-lg<?= $data['id_keluar']; ?>" class="btn btn-success btn-xs" title="Upload Berkas"><i class="fa fa-upload"></i></button>
                                                    <a href="download.php?filename=<?= $data['nm_qr'] ?>" class="btn btn-primary btn-xs" title="Download QR Code"><i class="fa fa-qrcode"></i></a>
                                                    <a href="index.php?page=edit_surat_keluar?id=<?= $data['id_keluar']; ?>" class="btn btn-warning btn-xs" title="Edit"><i class="fa fa-pencil-square-o"></i></a>
                                                    <a href="hapus_surat_keluar.php?id=<?= $data['id_keluar'] ?>" class="btn btn-danger btn-xs" title="Hapus" onclick="return confirm('Yakin akan dihapus ?')"><i class="fa fa-trash-o"></i></a>
                                                </div>
                                            </td>
                                    </tr>
                                    <div class="modal fade bs-example-modal-lg<?= $data['id_keluar']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title" id="myModalLabel">Form Uoload File SURAT
                                                        Keluar</h4>
                                                </div>
                                                <form action="" method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="id_keluar" value="<?= $data['id_keluar']; ?>">
                                                    <input type="hidden" name="no_surat" value="<?= $data['no_surat']; ?>">
                                                    <div class="modal-body">
                                                        <h4>Unggah Berkas</h4>
                                                        <div class="form-group">
                                                            <input type="file" name="berkas" id="" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                        <button type="submit" name="upload" class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                        }
                                ?>
                                </tbody>
                            </table>
                        </div>
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

if (isset($_POST['upload'])) {
    $id = $_POST['id_keluar'];
    // $no = $_POST['no_surat'];
    $no = 'berkas-' . rand(0, 99999);

    $ekstensi =  array('doc', 'docx', 'pdf');
    $filename = $_FILES['berkas']['name'];
    $ukuran = $_FILES['berkas']['size'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $extensi = explode('.', $filename);

    if (!in_array($ext, $ekstensi)) {
        echo "
				<script>
					alert('Maaf. Yang anda upload bukan berkasnya');
					window.location = 'keluar.php';
				</script
			";
    } else {

        $xx = $no . '.' . end($extensi);
        $file_lama = 'upload/surat_keluar/' . $xx;
        if (file_exists($file_lama)) {
            unlink($file_lama);
        }

        $sql2 = mysqli_query($conn, "UPDATE surat_keluar SET file = '$xx' WHERE id_keluar = '$id' ");
        move_uploaded_file($_FILES['berkas']['tmp_name'], 'upload/surat_keluar/' . $xx);

        if ($sql2) {
            echo "
				<script>
					alert('Upload berhasil');
					window.location = 'keluar.php';
				</script>
			";
        }
    }
}
?>