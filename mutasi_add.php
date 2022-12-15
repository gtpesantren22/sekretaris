<?php include 'head.php'; ?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Tambah Data Mutasi Santri
            <small>Data</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Data</a></li>
            <li class="active">Tambah Data Mutasi Santri</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Tambah Data Mutasi Santri
                        </h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="">Pilih Santri</label>
                                <div class="row">
                                    <div class="col-md-8">
                                        <select name="nis" id="pilihv2" class="form-control ">
                                            <option value=""> -pilih santri- </option>
                                            <?php
                                            $dt = mysqli_query($conn, "SELECT * FROM tb_santri WHERE aktif = 'Y' ");
                                            while ($kr = mysqli_fetch_assoc($dt)) {
                                            ?>
                                            <option value="<?= $kr['nis']; ?>"><?= $kr['nama']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-success btn-sm" type="submit" name="cek"><i
                                                class="fa fa-serch"></i> Cek</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div><!-- /.box-body -->
                    <?php
                    if (isset($_POST['cek'])) {
                        $nis = $_POST['nis'];
                        $dts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_santri WHERE nis = '$nis' "));
                    ?>
                    <div class="box-header">
                        <h3 class="box-title">Detail Data Santri</h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th>NIS</th>
                                        <th>:</th>
                                        <th><?= $dts['nis']; ?></th>
                                    </tr>
                                    <tr>
                                        <th>Nama</th>
                                        <th>:</th>
                                        <th><?= $dts['nama']; ?></th>
                                    </tr>
                                    <tr>
                                        <th>Alamat</th>
                                        <th>:</th>
                                        <th><?= $dts['desa'] . ' - ' . $dts['kec'] . ' - ' . $dts['kab']; ?></th>
                                    </tr>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>:</th>
                                        <th><?= $dts['k_formal'] . ' - ' . $dts['t_formal']; ?></th>
                                    </tr>
                                    <tr>
                                        <th>Kamar</th>
                                        <th>:</th>
                                        <th><?= $dts['kamar'] . ' / ' . $dts['komplek']; ?></th>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <form action="" method="post">
                                    <input type="hidden" name="nis" value="<?= $nis; ?>">
                                    <div class="form-group">
                                        <label for="">Alasan</label>
                                        <textarea name="alasan" id="" cols="30" rows="3" required
                                            class="form-control"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Tanggal Mutasi</label>
                                        <input type="date" name="tgl_mutasi" id="" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-success btn-sm" type="submit" name="simpan"><i
                                                class="fa fa-save"></i> Simpan Data</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <?php } ?>
                </div><!-- /.box -->

            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
</div><!-- /.content-wrapper -->
<!-- DataTables -->
<link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
<link rel="stylesheet" href="plugins/select2/select2.min.css">
<!-- jQuery 2.1.4 -->
<script src="plugins/jQuery/jQuery-2.1.4.min.js"></script>
<!-- Bootstrap 3.3.5 -->
<script src="bootstrap/js/bootstrap.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/select2.full.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables/dataTables.bootstrap.min.js"></script>
<script src="plugins/sw/sweetalert2.all.min.js"></script>
<script>
$(function() {
    $("#pilihv2").select2();
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

if (isset($_POST['simpan'])) {
    $nis = $_POST['nis'];
    $alasan = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['alasan']));
    $tgl_mutasi = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['tgl_mutasi']));

    $cek = mysqli_query($conn, "SELECT * FROM mutasi WHERE nis = '$nis' ");
    $dts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tb_santri WHERE nis = '$nis' "));

    $psn = '*INFORMASI MUTASI BARU*

*PERMOHONAN PENGECEKAN TANGGUNGAN SANTRI*
    
Nama : ' . $dts['nama'] . '
Alamat : ' . $dts['desa'] . '-' . $dts['kec'] . '-' . $dts['kab'] . '
Sekolah : ' . $dts['t_formal'] . '
Tgl Mutasi : ' . $tgl_mutasi . '

*_dimohon kepada BENDAHARA PESANTREN untuk segera mengecek tanggungan nya_*
Terimakasih';

    if (mysqli_num_rows($cek) > 0) {
        echo "
      <script>
        Swal.fire({
          icon: 'error',
          title: 'Maaf',
          text: 'Data ini sudah ada!'
        })
      </script>
    ";
    } else {
        $sql = mysqli_query($conn, "INSERT INTO mutasi VALUES ('', '$nis', '$alasan', '$tgl_mutasi', 0, '2022/2023') ");
        $sql2 = mysqli_query($conn_santri, "INSERT INTO mutasi VALUES ('', '$nis', '$alasan', '$tgl_mutasi', 0, '2022/2023') ");
        if ($sql && $sql2) {

            $curl2 = curl_init();
            curl_setopt_array(
                $curl2,
                array(
                    CURLOPT_URL => 'http://8.215.26.187:3000/api/sendMessageGroup',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => 'apiKey=f4064efa9d05f66f9be6151ec91ad846&id_group=120363028015516743@g.us&message=' . $psn,
                )
            );
            $response = curl_exec($curl2);
            curl_close($curl2);

            echo "
      <script>
      Swal.fire({
        icon: 'success',
        title: 'Sukses',
        text: 'Data berhasil tersimpan!'
      })
      window.location = 'mutasi.php';
      </script>
    ";
        }
    }
}
?>