<?php
// public_view_siswa.php
require_once 'config/database.php';
require_once 'core/functions.php'; // Untuk fungsi utility seperti get_bulan_indonesia

// Ambil public_link_hash dari URL
$public_link_hash = $_GET['hash'] ?? '';

if (empty($public_link_hash)) {
    die("Link tidak valid.");
}

// Query untuk mengambil data siswa berdasarkan hash
$stmt = $koneksi->prepare("
    SELECT s.id AS siswa_id, s.nis, s.nama_lengkap AS nama_siswa, s.alamat, s.no_telepon,
           k.nama_kelas, k.wali_kelas,
           u_ortu.nama_lengkap AS nama_ortu
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN users u_ortu ON s.ortu_user_id = u_ortu.id
    WHERE s.public_link_hash = ?
");
$stmt->bind_param("s", $public_link_hash);
$stmt->execute();
$result_siswa = $stmt->get_result();
$data_siswa = $result_siswa->fetch_assoc();
$stmt->close();

if (!$data_siswa) {
    die("Data siswa tidak ditemukan atau link sudah tidak berlaku.");
}

$siswa_id_for_data = $data_siswa['siswa_id'];

// Ambil data prestasi siswa ini
$query_prestasi = "
    SELECT ps.*, jp.nama_prestasi, jp.jenis, jp.tingkat
    FROM prestasi_siswa ps
    JOIN jenis_prestasi jp ON ps.jenis_prestasi_id = jp.id
    WHERE ps.siswa_id = ?
    ORDER BY ps.tanggal DESC
";
$stmt_prestasi = $koneksi->prepare($query_prestasi);
$stmt_prestasi->bind_param("i", $siswa_id_for_data);
$stmt_prestasi->execute();
$result_prestasi = $stmt_prestasi->get_result();
$data_prestasi = [];
while ($row = $result_prestasi->fetch_assoc()) {
    $data_prestasi[] = $row;
}
$stmt_prestasi->close();

// Ambil data pelanggaran siswa ini
$query_pelanggaran = "
    SELECT ps.*, jp.nama_pelanggaran, jp.poin
    FROM pelanggaran_siswa ps
    JOIN jenis_pelanggaran jp ON ps.jenis_pelanggaran_id = jp.id
    WHERE ps.siswa_id = ?
    ORDER BY ps.tanggal DESC
";
$stmt_pelanggaran = $koneksi->prepare($query_pelanggaran);
$stmt_pelanggaran->bind_param("i", $siswa_id_for_data);
$stmt_pelanggaran->execute();
$result_pelanggaran = $stmt_pelanggaran->get_result();
$data_pelanggaran = [];
$total_poin_pelanggaran = 0;
while ($row = $result_pelanggaran->fetch_assoc()) {
    $data_pelanggaran[] = $row;
    $total_poin_pelanggaran += $row['poin'];
}
$stmt_pelanggaran->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Kesiswaan <?php echo htmlspecialchars($data_siswa['nama_siswa']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 30px; margin-bottom: 30px; }
        .card-header { background-color: #0d6efd; color: white; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0">Rekap Kesiswaan</h4>
                <h5 class="mb-0"><?php echo NAMA_SEKOLAH_LENGKAP; ?></h5>
            </div>
            <div class="card-body">
                <h3 class="mb-3">Informasi Siswa</h3>
                <table class="table table-bordered mb-4">
                    <tr>
                        <th width="180px">NIS</th>
                        <td><?php echo htmlspecialchars($data_siswa['nis']); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td><strong><?php echo htmlspecialchars($data_siswa['nama_siswa']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td><?php echo htmlspecialchars($data_siswa['nama_kelas']); ?></td>
                    </tr>
                    <tr>
                        <th>Wali Kelas</th>
                        <td><?php echo htmlspecialchars($data_siswa['wali_kelas']); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Orang Tua</th>
                        <td><?php echo htmlspecialchars($data_siswa['nama_ortu'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td><?php echo htmlspecialchars($data_siswa['alamat']); ?></td>
                    </tr>
                </table>

                <h3 class="mb-3">Riwayat Prestasi (Total: <?php echo count($data_prestasi); ?>)</h3>
                <?php if (count($data_prestasi) > 0): ?>
                <table class="table table-bordered table-striped mb-4">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Prestasi</th>
                            <th>Jenis</th>
                            <th>Tingkat</th>
                            <th>Keterangan</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data_prestasi as $prestasi): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($prestasi['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($prestasi['nama_prestasi']); ?></td>
                            <td><?php echo htmlspecialchars($prestasi['jenis']); ?></td>
                            <td><?php echo htmlspecialchars($prestasi['tingkat']); ?></td>
                            <td><?php echo htmlspecialchars($prestasi['keterangan']); ?></td>
                            <td>
                                <?php if (!empty($prestasi['file_bukti'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/uploads/prestasi/<?php echo $prestasi['file_bukti']; ?>" target="_blank" class="btn btn-sm btn-info">Lihat</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">Tidak ada data prestasi tercatat untuk siswa ini.</p>
                <?php endif; ?>

                <h3 class="mb-3">Riwayat Pelanggaran (Total Poin: <?php echo $total_poin_pelanggaran; ?>)</h3>
                <?php if (count($data_pelanggaran) > 0): ?>
                <table class="table table-bordered table-striped mb-4">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pelanggaran</th>
                            <th>Poin</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data_pelanggaran as $pelanggaran): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($pelanggaran['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars($pelanggaran['nama_pelanggaran']); ?></td>
                            <td><span class="badge bg-danger"><?php echo htmlspecialchars($pelanggaran['poin']); ?></span></td>
                            <td><?php echo htmlspecialchars($pelanggaran['keterangan']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">Tidak ada data pelanggaran tercatat untuk siswa ini.</p>
                <?php endif; ?>

                <div class="text-center mt-5">
                    <p class="text-muted">Data ini disajikan oleh Bagian Kesiswaan <?php echo NAMA_SEKOLAH_LENGKAP; ?>.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>