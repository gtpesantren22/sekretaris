<?php include 'head.php'; ?>
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Data Mutasi Santri
      <small>Data</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
      <li class="active">Data Mutasi Santri</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Data Mutasi Santri
            </h3>
            <a href="mutasi_add.php" class="btn btn-primary pull-right btn-sm"><i class="fa fa-plus-circle"></i> Tambah Mutasi Baru</a>
          </div><!-- /.box-header -->
          <div class="box-body">
            <div class="table-responsive">
              <table id="example1_bst" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Alasan</th>
                    <th>Tgl Mutasi</th>
                    <th>Status</th>
                    <th>#</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  $sql = mysqli_query($conn, "SELECT a.*, b.* FROM mutasi a JOIN tb_santri b ON a.nis=b.nis WHERE  aktif = 'Y' ORDER BY id_mutasi DESC ");
                  while ($dt = mysqli_fetch_assoc($sql)) {
                    if ($dt['status'] == 0) {
                      $stas = "<span class='label label-danger'><i class='fa fa-times'></i> Verval Bendahara</span> | <span class='label label-danger'><i class='fa fa-times'></i> Kirim ke Pendataan</span>";
                    } elseif ($dt['status'] == 1) {
                      $stas = "<span class='label label-success'><i class='fa fa-check'></i> Verval Bendahara</span> | <span class='label label-danger'><i class='fa fa-times'></i> Kirim ke Pendataan</span>";
                    } elseif ($dt['status'] == 2) {
                      $stas = "<span class='label label-success'><i class='fa fa-check'></i> Verval Bendahara</span> | <span class='label label-success'><i class='fa fa-check'></i> Kirim ke Pendataan</span>";
                    }
                  ?>
                    <tr>
                      <td><?= $no++; ?></td>
                      <td><?= $dt['nis']; ?></td>
                      <td><?= $dt['nama']; ?></td>
                      <td><?= $dt['alasan']; ?></td>
                      <td><?= $dt['tgl_mutasi']; ?></td>
                      <td><?= $stas; ?></td>
                      <td>
                        <?php if ($dt['status'] == 0) { ?>

                          <a class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan dihapus ?')" href="<?= 'hapus.php?kd=mts&id=' . $dt['id_mutasi']; ?>"><i class="fa fa-trash"></i></a>

                        <?php } elseif ($dt['status'] == 1) { ?>
                          <form action="" method="post">
                            <input type="hidden" name="id_mutasi" value="<?= $dt['id_mutasi']; ?>">
                            <a class="btn btn-danger btn-xs" onclick="return confirm('Yakin akan dihapus ?')" href="<?= 'hapus.php?kd=mts&id=' . $dt['id_mutasi']; ?>"><i class="fa fa-trash"></i></a>
                            <button class="btn btn-xs btn-primary" type="submit" name="send"><i class="fa fa-send"></i> kirim</button>
                          </form>
                        <?php } elseif ($dt['status'] == 2) {
                        } ?>
                      </td>
                    </tr>
                  <?php } ?>
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
<script src="plugins/sw/sweetalert2.all.min.js"></script>
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

if (isset($_POST['send'])) {
  $id_mutasi = $_POST['id_mutasi'];
  $sql = mysqli_query($conn, "UPDATE mutasi SET status = 2 WHERE id_mutasi = '$id_mutasi' ");
  $sql2 = mysqli_query($conn_santri, "UPDATE mutasi SET status = 2 WHERE id_mutasi = '$id_mutasi' ");

  if ($sql2 && $sql) {
    echo "
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses',
                    text: 'Data mutasi sudah terkirim',
                    showConfirmButton: false
                });
                var millisecondsToWait = 1000;
                setTimeout(function() {
                    document.location.href = 'mutasi.php' 
                }, millisecondsToWait);
            </script>
        ";
  }
}
?>