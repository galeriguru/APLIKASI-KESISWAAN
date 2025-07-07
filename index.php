<?php
session_start();
require_once 'config/database.php';
require_once 'core/functions.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\{QRCode, QROptions};

$action = $_GET['action'] ?? null;

if ($action === 'process_login') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $koneksi->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        }
    }
    header('Location: login.php?error=1');
    exit;
} elseif ($action === 'generate_qr_code') {
    $siswa_id = $_GET['siswa_id'] ?? 0;
    if ($siswa_id == 0) { header('Content-Type: image/png'); readfile('assets/default_qr_error.png'); exit; }
    $stmt_hash = $koneksi->prepare("SELECT public_link_hash FROM siswa WHERE id = ?");
    $stmt_hash->bind_param("i", $siswa_id); $stmt_hash->execute();
    $siswa_data_hash = $stmt_hash->get_result()->fetch_assoc(); $stmt_hash->close();
    $public_hash = $siswa_data_hash['public_link_hash'];
    if (empty($public_hash)) {
        do { $public_hash = bin2hex(random_bytes(32)); $check_unique = $koneksi->query("SELECT id FROM siswa WHERE public_link_hash = '{$public_hash}'")->num_rows; } while ($check_unique > 0);
        $stmt_update_hash = $koneksi->prepare("UPDATE siswa SET public_link_hash = ? WHERE id = ?");
        $stmt_update_hash->bind_param("si", $public_hash, $siswa_id); $stmt_update_hash->execute(); $stmt_update_hash->close();
    }
    $qr_data_url = BASE_URL . "/public_view_siswa.php?hash=" . $public_hash;
    $options = new QROptions([ 'outputType' => QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => QRCode::ECC_L, 'scale' => 8, 'imageTransparent' => false, 'bgColor' => [255, 255, 255], 'fgColor' => [0, 0, 0], ]);
    $qrcode = new QRCode($options);
    header('Content-Type: image/png'); echo $qrcode->render($qr_data_url); exit;
}

if (isset($_SESSION['is_logged_in'])) {
    $role = $_SESSION['role'];
    if ($role === 'pembina') {
        switch ($action) {
            case 'proses_tambah_siswa':
                $nis = $_POST['nis']; $nama_lengkap = $_POST['nama_lengkap']; $kelas_id = $_POST['kelas_id']; $alamat = $_POST['alamat']; $no_telepon = $_POST['no_telepon']; $nama_ortu = $_POST['nama_ortu'];
                $koneksi->begin_transaction();
                try {
                    $username_siswa = $nis; $password_siswa = password_hash($nis, PASSWORD_DEFAULT);
                    $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'siswa')");
                    $stmt->bind_param("sss", $username_siswa, $password_siswa, $nama_lengkap); $stmt->execute(); $user_id_siswa = $koneksi->insert_id;
                    $username_ortu = 'ortu_' . $nis; $password_ortu = password_hash($nis, PASSWORD_DEFAULT);
                    $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'orang_tua')");
                    $stmt->bind_param("sss", $username_ortu, $password_ortu, $nama_ortu); $stmt->execute(); $user_id_ortu = $koneksi->insert_id;
                    do { $public_hash_new_siswa = bin2hex(random_bytes(32)); $check_unique = $koneksi->query("SELECT id FROM siswa WHERE public_link_hash = '{$public_hash_new_siswa}'")->num_rows; } while ($check_unique > 0);
                    $stmt = $koneksi->prepare("INSERT INTO siswa (user_id, ortu_user_id, kelas_id, nis, nama_lengkap, alamat, no_telepon, public_link_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiisssss", $user_id_siswa, $user_id_ortu, $kelas_id, $nis, $nama_lengkap, $alamat, $no_telepon, $public_hash_new_siswa); $stmt->execute();
                    $koneksi->commit();
                    header('Location: index.php?action=data_siswa&status=sukses_tambah');
                } catch (mysqli_sql_exception $exception) { $koneksi->rollback(); header('Location: index.php?action=data_siswa&status=gagal_tambah'); }
                exit;
            case 'proses_ubah_siswa':
                $id_siswa = $_POST['id_siswa']; $user_id_siswa = $_POST['user_id_siswa']; $ortu_user_id = $_POST['ortu_user_id']; $nis = $_POST['nis']; $nama_lengkap = $_POST['nama_lengkap']; $kelas_id = $_POST['kelas_id']; $alamat = $_POST['alamat']; $no_telepon = $_POST['no_telepon']; $nama_ortu = $_POST['nama_ortu'];
                $koneksi->begin_transaction();
                try {
                    $stmt_siswa = $koneksi->prepare("UPDATE siswa SET nis=?, nama_lengkap=?, kelas_id=?, alamat=?, no_telepon=? WHERE id=?");
                    $stmt_siswa->bind_param("ssissi", $nis, $nama_lengkap, $kelas_id, $alamat, $no_telepon, $id_siswa); $stmt_siswa->execute();
                    $stmt_user_siswa = $koneksi->prepare("UPDATE users SET nama_lengkap=? WHERE id=?");
                    $stmt_user_siswa->bind_param("si", $nama_lengkap, $user_id_siswa); $stmt_user_siswa->execute();
                    $stmt_user_ortu = $koneksi->prepare("UPDATE users SET nama_lengkap=? WHERE id=?");
                    $stmt_user_ortu->bind_param("si", $nama_ortu, $ortu_user_id); $stmt_user_ortu->execute();
                    $koneksi->commit();
                    header('Location: index.php?action=data_siswa&status=sukses_ubah');
                } catch (mysqli_sql_exception $exception) { $koneksi->rollback(); header('Location: index.php?action=data_siswa&status=gagal_ubah'); }
                exit;
            case 'hapus_siswa':
                $id_siswa = $_GET['id'] ?? 0;
                $stmt_get_ids = $koneksi->prepare("SELECT user_id, ortu_user_id FROM siswa WHERE id = ?");
                $stmt_get_ids->bind_param("i", $id_siswa); $stmt_get_ids->execute();
                $ids = $stmt_get_ids->get_result()->fetch_assoc(); $stmt_get_ids->close();
                if ($ids) {
                    $koneksi->begin_transaction();
                    try {
                        $stmt_siswa = $koneksi->prepare("DELETE FROM siswa WHERE id = ?");
                        $stmt_siswa->bind_param("i", $id_siswa); $stmt_siswa->execute(); $stmt_siswa->close();
                        $stmt_user_siswa = $koneksi->prepare("DELETE FROM users WHERE id = ?");
                        $stmt_user_siswa->bind_param("i", $ids['user_id']); $stmt_user_siswa->execute(); $stmt_user_siswa->close();
                        if ($ids['ortu_user_id']) {
                            $stmt_user_ortu = $koneksi->prepare("DELETE FROM users WHERE id = ?");
                            $stmt_user_ortu->bind_param("i", $ids['ortu_user_id']); $stmt_user_ortu->execute(); $stmt_user_ortu->close();
                        }
                        $koneksi->commit();
                        header('Location: index.php?action=data_siswa&status=sukses_hapus');
                    } catch (mysqli_sql_exception $exception) { $koneksi->rollback(); header('Location: index.php?action=data_siswa&status=gagal_hapus'); }
                } else { header('Location: index.php?action=data_siswa&status=gagal_hapus'); }
                exit;
            case 'proses_impor_siswa':
                $file = $_FILES['file_excel']['tmp_name']; $skipped_rows_count = 0; $warning_messages = [];
                try {
                    $spreadsheet = IOFactory::load($file); $sheet = $spreadsheet->getActiveSheet(); $highestRow = $sheet->getHighestRow();
                    $koneksi->begin_transaction();
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $nis = $sheet->getCell('A' . $row)->getValue(); $nama_lengkap = $sheet->getCell('B' . $row)->getValue(); $nama_kelas_excel = $sheet->getCell('C' . $row)->getValue(); $alamat = $sheet->getCell('D' . $row)->getValue(); $no_telepon = $sheet->getCell('E' . $row)->getValue(); $nama_ortu = $sheet->getCell('F' . $row)->getValue();
                        if (empty($nis) || empty($nama_lengkap) || empty($nama_kelas_excel) || empty($nama_ortu)) {
                            $skipped_rows_count++; continue;
                        }
                        $stmt_kelas_lookup = $koneksi->prepare("SELECT id FROM kelas WHERE nama_kelas = ?"); $stmt_kelas_lookup->bind_param("s", $nama_kelas_excel); $stmt_kelas_lookup->execute();
                        $result_kelas_lookup = $stmt_kelas_lookup->get_result(); $kelas_data = $result_kelas_lookup->fetch_assoc(); $stmt_kelas_lookup->close();
                        if (!$kelas_data) {
                            $skipped_rows_count++; continue;
                        }
                        $kelas_id = $kelas_data['id'];
                        $username_siswa = $nis; $password_siswa = password_hash($nis, PASSWORD_DEFAULT);
                        $stmt_us = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'siswa')"); $stmt_us->bind_param("sss", $username_siswa, $password_siswa, $nama_lengkap);
                        if (!$stmt_us->execute()) { $skipped_rows_count++; continue; }
                        $user_id_siswa = $koneksi->insert_id;
                        $username_ortu = 'ortu_' . $nis; $password_ortu = password_hash($nis, PASSWORD_DEFAULT);
                        $stmt_uo = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'orang_tua')"); $stmt_uo->bind_param("sss", $username_ortu, $password_ortu, $nama_ortu);
                        if (!$stmt_uo->execute()) { $skipped_rows_count++; continue; }
                        $user_id_ortu = $koneksi->insert_id;
                        do { $public_hash_new_siswa = bin2hex(random_bytes(32)); $check_unique = $koneksi->query("SELECT id FROM siswa WHERE public_link_hash = '{$public_hash_new_siswa}'")->num_rows; } while ($check_unique > 0);
                        $stmt_s = $koneksi->prepare("INSERT INTO siswa (user_id, ortu_user_id, kelas_id, nis, nama_lengkap, alamat, no_telepon, public_link_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_s->bind_param("iiisssss", $user_id_siswa, $user_id_ortu, $kelas_id, $nis, $nama_lengkap, $alamat, $no_telepon, $public_hash_new_siswa); $stmt_s->execute();
                    }
                    $koneksi->commit();
                    if ($skipped_rows_count > 0) {
                        $message = "{$skipped_rows_count} baris dilewati karena data tidak valid.";
                        header('Location: index.php?action=data_siswa&status=impor_warning&message=' . urlencode($message));
                    } else { header('Location: index.php?action=data_siswa&status=sukses_impor'); }
                } catch (Exception $e) { $koneksi->rollback(); error_log("Import Error: " . $e->getMessage()); header('Location: index.php?action=data_siswa&status=gagal_impor'); }
                exit;
            case 'proses_tambah_kelas':
                $nama_kelas = $_POST['nama_kelas']; $wali_kelas = $_POST['wali_kelas'];
                $stmt = $koneksi->prepare("INSERT INTO kelas (nama_kelas, wali_kelas) VALUES (?, ?)"); $stmt->bind_param("ss", $nama_kelas, $wali_kelas);
                if ($stmt->execute()) { header('Location: index.php?action=manajemen_kelas&status=sukses_tambah_kelas'); } else { header('Location: index.php?action=manajemen_kelas&status=gagal_tambah_kelas'); }
                exit;
            case 'proses_ubah_kelas':
                $id = $_POST['id']; $nama_kelas = $_POST['nama_kelas']; $wali_kelas = $_POST['wali_kelas'];
                $stmt = $koneksi->prepare("UPDATE kelas SET nama_kelas=?, wali_kelas=? WHERE id=?"); $stmt->bind_param("ssi", $nama_kelas, $wali_kelas, $id);
                if ($stmt->execute()) { header('Location: index.php?action=manajemen_kelas&status=sukses_ubah_kelas'); } else { header('Location: index.php?action=manajemen_kelas&status=gagal_ubah_kelas'); }
                exit;
            case 'hapus_kelas':
                $id = $_GET['id'];
                $stmt_check_siswa = $koneksi->prepare("SELECT COUNT(*) AS total_siswa FROM siswa WHERE kelas_id = ?"); $stmt_check_siswa->bind_param("i", $id); $stmt_check_siswa->execute();
                $count_siswa = $stmt_check_siswa->get_result()->fetch_assoc()['total_siswa']; $stmt_check_siswa->close();
                if ($count_siswa > 0) { header('Location: index.php?action=manajemen_kelas&status=gagal_hapus_kelas_siswa'); exit; }
                $stmt = $koneksi->prepare("DELETE FROM kelas WHERE id=?"); $stmt->bind_param("i", $id);
                if ($stmt->execute()) { header('Location: index.php?action=manajemen_kelas&status=sukses_hapus_kelas'); } else { header('Location: index.php?action=manajemen_kelas&status=gagal_hapus_kelas'); }
                exit;
            case 'proses_tambah_jenis_prestasi':
                $nama = $_POST['nama_prestasi']; $jenis = $_POST['jenis']; $tingkat = $_POST['tingkat'];
                $stmt = $koneksi->prepare("INSERT INTO jenis_prestasi (nama_prestasi, jenis, tingkat) VALUES (?, ?, ?)"); $stmt->bind_param("sss", $nama, $jenis, $tingkat); $stmt->execute();
                header('Location: index.php?action=jenis_prestasi'); exit;
            case 'proses_ubah_jenis_prestasi':
                $id = $_POST['id']; $nama = $_POST['nama_prestasi']; $jenis = $_POST['jenis']; $tingkat = $_POST['tingkat'];
                $stmt = $koneksi->prepare("UPDATE jenis_prestasi SET nama_prestasi=?, jenis=?, tingkat=? WHERE id=?"); $stmt->bind_param("sssi", $nama, $jenis, $tingkat, $id); $stmt->execute();
                header('Location: index.php?action=jenis_prestasi'); exit;
            case 'hapus_jenis_prestasi':
                $id = $_GET['id'];
                $stmt = $koneksi->prepare("DELETE FROM jenis_prestasi WHERE id=?"); $stmt->bind_param("i", $id); $stmt->execute();
                header('Location: index.php?action=jenis_prestasi'); exit;
            case 'proses_tambah_prestasi_siswa':
                $siswa_id = $_POST['siswa_id']; $jenis_prestasi_id = $_POST['jenis_prestasi_id']; $tanggal = $_POST['tanggal']; $keterangan = $_POST['keterangan']; $file_bukti_nama = '';
                if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "uploads/prestasi/"; $file_bukti_nama = uniqid() . '-' . basename($_FILES["file_bukti"]["name"]); $target_file = $target_dir . $file_bukti_nama;
                    move_uploaded_file($_FILES["file_bukti"]["tmp_name"], $target_file);
                }
                $stmt = $koneksi->prepare("INSERT INTO prestasi_siswa (siswa_id, jenis_prestasi_id, tanggal, keterangan, file_bukti) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $siswa_id, $jenis_prestasi_id, $tanggal, $keterangan, $file_bukti_nama); $stmt->execute();
                header('Location: index.php?action=prestasi_siswa'); exit;
            case 'hapus_prestasi_siswa':
                $id = $_GET['id'];
                $stmt = $koneksi->prepare("SELECT file_bukti FROM prestasi_siswa WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt_delete = $koneksi->prepare("DELETE FROM prestasi_siswa WHERE id = ?"); $stmt_delete->bind_param("i", $id);
                if ($stmt_delete->execute() && $result && !empty($result['file_bukti'])) {
                    $file_path = 'uploads/prestasi/' . $result['file_bukti']; if (file_exists($file_path)) { unlink($file_path); }
                }
                header('Location: index.php?action=prestasi_siswa'); exit;
            case 'proses_tambah_jenis_pelanggaran':
                $nama = $_POST['nama_pelanggaran']; $poin = $_POST['poin'];
                $stmt = $koneksi->prepare("INSERT INTO jenis_pelanggaran (nama_pelanggaran, poin) VALUES (?, ?)"); $stmt->bind_param("si", $nama, $poin); $stmt->execute();
                header('Location: index.php?action=jenis_pelanggaran'); exit;
            case 'proses_ubah_jenis_pelanggaran':
                $id = $_POST['id']; $nama = $_POST['nama_pelanggaran']; $poin = $_POST['poin'];
                $stmt = $koneksi->prepare("UPDATE jenis_pelanggaran SET nama_pelanggaran=?, poin=? WHERE id=?"); $stmt->bind_param("sii", $nama, $poin, $id); $stmt->execute();
                header('Location: index.php?action=jenis_pelanggaran'); exit;
            case 'hapus_jenis_pelanggaran':
                $id = $_GET['id'];
                $stmt = $koneksi->prepare("DELETE FROM jenis_pelanggaran WHERE id=?"); $stmt->bind_param("i", $id); $stmt->execute();
                header('Location: index.php?action=jenis_pelanggaran'); exit;
            case 'proses_tambah_pelanggaran_siswa':
                $siswa_id = $_POST['siswa_id']; $jenis_pelanggaran_id = $_POST['jenis_pelanggaran_id']; $tanggal = $_POST['tanggal']; $keterangan = $_POST['keterangan']; $pencatat_id = $_SESSION['user_id'];
                $koneksi->begin_transaction();
                try {
                    $stmt = $koneksi->prepare("INSERT INTO pelanggaran_siswa (siswa_id, jenis_pelanggaran_id, tanggal, keterangan, dicatat_oleh_user_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisss", $siswa_id, $jenis_pelanggaran_id, $tanggal, $keterangan, $pencatat_id); $stmt->execute(); $stmt->close();
                    $stmt_notif_data = $koneksi->prepare("SELECT s.nama_lengkap AS nama_siswa, s.nis, s.no_telepon AS siswa_phone, u_ortu.nama_lengkap AS nama_ortu, s.no_telepon AS ortu_phone, jp.nama_pelanggaran, jp.poin FROM siswa s LEFT JOIN users u_ortu ON s.ortu_user_id = u_ortu.id JOIN jenis_pelanggaran jp ON jp.id = ? WHERE s.id = ?");
                    $stmt_notif_data->bind_param("ii", $jenis_pelanggaran_id, $siswa_id); $stmt_notif_data->execute(); $notif_data = $stmt_notif_data->get_result()->fetch_assoc(); $stmt_notif_data->close();
                    if ($notif_data) {
                        $stmt_total_poin = $koneksi->prepare("SELECT SUM(jp.poin) AS total_poin FROM pelanggaran_siswa ps JOIN jenis_pelanggaran jp ON ps.jenis_pelanggaran_id = jp.id WHERE ps.siswa_id = ?");
                        $stmt_total_poin->bind_param("i", $siswa_id); $stmt_total_poin->execute();
                        $total_poin_siswa = $stmt_total_poin->get_result()->fetch_assoc()['total_poin'] ?? 0; $stmt_total_poin->close();
                        if ($total_poin_siswa >= NOTIFICATION_POIN_THRESHOLD && !empty($notif_data['ortu_phone'])) {
                            $msg = "Yth. Bapak/Ibu " . $notif_data['nama_ortu'] . ",\n\n" . "Dengan ini kami memberitahukan bahwa putra/putri Anda, an. " . $notif_data['nama_siswa'] . " (NIS: " . $notif_data['nis'] . "), pada tanggal " . date('d-m-Y', strtotime($tanggal)) . " telah melakukan pelanggaran:\n" . "- Jenis Pelanggaran: " . $notif_data['nama_pelanggaran'] . " (" . $notif_data['poin'] . " Poin)\n" . "Total poin pelanggaran saat ini: " . $total_poin_siswa . " poin.\n\n" . "Mohon perhatiannya. Terima kasih.\n" . NAMA_SEKOLAH_LENGKAP;
                            send_notification($notif_data['ortu_phone'], $msg, 'whatsapp');
                        }
                    }
                    $koneksi->commit(); header('Location: index.php?action=pelanggaran_siswa');
                } catch (mysqli_sql_exception $exception) { $koneksi->rollback(); header('Location: index.php?action=pelanggaran_siswa&status=gagal_tambah_pelanggaran'); }
                exit;
            case 'hapus_pelanggaran_siswa':
                $id = $_GET['id'];
                $stmt = $koneksi->prepare("DELETE FROM pelanggaran_siswa WHERE id=?"); $stmt->bind_param("i", $id); $stmt->execute();
                header('Location: index.php?action=pelanggaran_siswa'); exit;
            case 'generate_surat_panggilan':
                $siswa_id = $_GET['siswa_id'] ?? 0;
                if ($siswa_id == 0) { die("ID Siswa tidak valid untuk generasi surat."); }
                $stmt_data = $koneksi->prepare("SELECT s.nis, s.nama_lengkap AS nama_siswa, k.nama_kelas, u_ortu.nama_lengkap AS nama_ortu, SUM(jp.poin) AS total_poin_pelanggaran FROM siswa s JOIN kelas k ON s.kelas_id = k.id LEFT JOIN users u_ortu ON s.ortu_user_id = u_ortu.id LEFT JOIN pelanggaran_siswa ps ON s.id = ps.siswa_id LEFT JOIN jenis_pelanggaran jp ON ps.jenis_pelanggaran_id = jp.id WHERE s.id = ? GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, u_ortu.nama_lengkap");
                $stmt_data->bind_param("i", $siswa_id); $stmt_data->execute();
                $data = $stmt_data->get_result()->fetch_assoc(); $stmt_data->close();
                if (!$data || $data['total_poin_pelanggaran'] < AMBANG_BATAS_POIN_PANGGILAN) { die("Data siswa tidak ditemukan atau poin pelanggaran belum mencapai ambang batas."); }
                $pembina_kesiswaan_nama = $_SESSION['nama_lengkap'];
                ob_start(); include 'templates/surat_panggilan_template.php'; $html = ob_get_clean();
                $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options); $dompdf->loadHtml($html);
                $dompdf->setPaper('F4', 'portrait');
                $dompdf->render();
                $filename = "Surat_Panggilan_".$data['nis']."_".date('YmdHis').".pdf";
                $filepath_to_save = "uploads/surat/" . $filename;
                file_put_contents($filepath_to_save, $dompdf->output());
                $stmt_log = $koneksi->prepare("INSERT INTO surat_panggilan_log (siswa_id, tanggal_panggilan, poin_saat_panggilan, dicatat_oleh_user_id, file_surat) VALUES (?, ?, ?, ?, ?)");
                $tanggal_sekarang = date('Y-m-d'); $user_id_pembina = $_SESSION['user_id'];
                $stmt_log->bind_param("iisss", $siswa_id, $tanggal_sekarang, $data['total_poin_pelanggaran'], $user_id_pembina, $filename);
                $stmt_log->execute(); $stmt_log->close();
                $dompdf->stream($filename, ["Attachment" => true]);
                exit;
            case 'proses_tambah_agenda_kesiswaan':
                $judul = $_POST['judul_kegiatan']; $deskripsi = $_POST['deskripsi']; $tgl_mulai = $_POST['tanggal_mulai']; $tgl_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null; $created_by = $_SESSION['user_id'];
                $stmt = $koneksi->prepare("INSERT INTO agenda_kesiswaan (judul_kegiatan, deskripsi, tanggal_mulai, tanggal_selesai, created_by_user_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $judul, $deskripsi, $tgl_mulai, $tgl_selesai, $created_by); $stmt->execute();
                header('Location: index.php?action=agenda_kesiswaan'); exit;
            case 'proses_ubah_agenda_kesiswaan':
                $id = $_POST['id']; $judul = $_POST['judul_kegiatan']; $deskripsi = $_POST['deskripsi']; $tgl_mulai = $_POST['tanggal_mulai']; $tgl_selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
                $stmt = $koneksi->prepare("UPDATE agenda_kesiswaan SET judul_kegiatan=?, deskripsi=?, tanggal_mulai=?, tanggal_selesai=? WHERE id=?");
                $stmt->bind_param("ssssi", $judul, $deskripsi, $tgl_mulai, $tgl_selesai, $id); $stmt->execute();
                header('Location: index.php?action=agenda_kesiswaan'); exit;
            case 'hapus_agenda_kesiswaan':
                $id = $_GET['id'];
                $stmt = $koneksi->prepare("DELETE FROM agenda_kesiswaan WHERE id=?"); $stmt->bind_param("i", $id); $stmt->execute();
                header('Location: index.php?action=agenda_kesiswaan'); exit;
            case 'laporan_prestasi':
            case 'generate_laporan_prestasi_pdf':
            case 'generate_laporan_prestasi_excel':
                $filter_bulan = $_GET['bulan'] ?? ''; $filter_tahun = $_GET['tahun'] ?? ''; $filter_kelas = $_GET['kelas_id'] ?? ''; $filter_siswa = $_GET['siswa_id'] ?? ''; $filter_jenis_prestasi = $_GET['jenis_prestasi_id'] ?? '';
                $query_report_data = "SELECT ps.*, s.nis, s.nama_lengkap AS nama_siswa, k.nama_kelas, jp.nama_prestasi, jp.jenis, jp.tingkat FROM prestasi_siswa ps JOIN siswa s ON ps.siswa_id = s.id JOIN kelas k ON s.kelas_id = k.id JOIN jenis_prestasi jp ON ps.jenis_prestasi_id = jp.id WHERE 1=1";
                if (!empty($filter_bulan)) $query_report_data .= " AND MONTH(ps.tanggal) = " . (int)$filter_bulan;
                if (!empty($filter_tahun)) $query_report_data .= " AND YEAR(ps.tanggal) = " . (int)$filter_tahun;
                if (!empty($filter_kelas)) $query_report_data .= " AND s.kelas_id = " . (int)$filter_kelas;
                if (!empty($filter_siswa)) $query_report_data .= " AND s.id = " . (int)$filter_siswa;
                if (!empty($filter_jenis_prestasi)) $query_report_data .= " AND jp.id = " . (int)$filter_jenis_prestasi;
                $query_report_data .= " ORDER BY ps.tanggal DESC, s.nama_lengkap ASC";
                $result_report = mysqli_query($koneksi, $query_report_data); $report_data = mysqli_fetch_all($result_report, MYSQLI_ASSOC);
                if ($action === 'generate_laporan_prestasi_pdf' || $action === 'generate_laporan_prestasi_excel'){
                    $pembina_kesiswaan_nama = $_SESSION['nama_lengkap'];
                    if ($action === 'generate_laporan_prestasi_pdf') {
                        ob_start(); include 'templates/laporan_prestasi_pdf_template.php'; $html = ob_get_clean();
                        $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
                        $dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
                        $dompdf->stream("Laporan_Prestasi_".date('YmdHis').".pdf", ["Attachment" => true]);
                    } elseif ($action === 'generate_laporan_prestasi_excel') {
                        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Laporan Prestasi Siswa');
                        $headers = ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Nama Prestasi', 'Jenis', 'Tingkat', 'Keterangan'];
                        $sheet->fromArray($headers, NULL, 'A1'); $row_num = 2;
                        foreach ($report_data as $index => $row) {
                            $sheet->setCellValue('A'.$row_num, $index + 1); $sheet->setCellValue('B'.$row_num, date('d-m-Y', strtotime($row['tanggal']))); $sheet->setCellValue('C'.$row_num, $row['nis']); $sheet->setCellValue('D'.$row_num, $row['nama_siswa']); $sheet->setCellValue('E'.$row_num, $row['nama_kelas']); $sheet->setCellValue('F'.$row_num, $row['nama_prestasi']); $sheet->setCellValue('G'.$row_num, $row['jenis']); $sheet->setCellValue('H'.$row_num, $row['tingkat']); $sheet->setCellValue('I'.$row_num, $row['keterangan']); $row_num++;
                        }
                        foreach (range('A', $sheet->getHighestColumn()) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="Laporan_Prestasi_Siswa_'.date('YmdHis').'.xlsx"'); header('Cache-Control: max-age=0');
                        $writer = new Xlsx($spreadsheet); $writer->save('php://output');
                    }
                    exit;
                }
                break;
            case 'laporan_pelanggaran':
            case 'generate_laporan_pelanggaran_pdf':
            case 'generate_laporan_pelanggaran_excel':
                $filter_bulan = $_GET['bulan'] ?? ''; $filter_tahun = $_GET['tahun'] ?? ''; $filter_kelas = $_GET['kelas_id'] ?? ''; $filter_siswa = $_GET['siswa_id'] ?? ''; $filter_jenis_pelanggaran = $_GET['jenis_pelanggaran_id'] ?? '';
                $query_report_data = "SELECT ps.*, s.nis, s.nama_lengkap AS nama_siswa, k.nama_kelas, jp.nama_pelanggaran, jp.poin, u.nama_lengkap AS nama_pencatat FROM pelanggaran_siswa ps JOIN siswa s ON ps.siswa_id = s.id JOIN kelas k ON s.kelas_id = k.id JOIN jenis_pelanggaran jp ON ps.jenis_pelanggaran_id = jp.id LEFT JOIN users u ON ps.dicatat_oleh_user_id = u.id WHERE 1=1";
                if (!empty($filter_bulan)) $query_report_data .= " AND MONTH(ps.tanggal) = " . (int)$filter_bulan;
                if (!empty($filter_tahun)) $query_report_data .= " AND YEAR(ps.tanggal) = " . (int)$filter_tahun;
                if (!empty($filter_kelas)) $query_report_data .= " AND s.kelas_id = " . (int)$filter_kelas;
                if (!empty($filter_siswa)) $query_report_data .= " AND s.id = " . (int)$filter_siswa;
                if (!empty($filter_jenis_pelanggaran)) $query_report_data .= " AND jp.id = " . (int)$filter_jenis_pelanggaran;
                $query_report_data .= " ORDER BY ps.tanggal DESC, s.nama_lengkap ASC";
                $result_report = mysqli_query($koneksi, $query_report_data); $report_data = mysqli_fetch_all($result_report, MYSQLI_ASSOC);
                if ($action === 'generate_laporan_pelanggaran_pdf' || $action === 'generate_laporan_pelanggaran_excel'){
                    $pembina_kesiswaan_nama = $_SESSION['nama_lengkap'];
                    if ($action === 'generate_laporan_pelanggaran_pdf') {
                        ob_start(); include 'templates/laporan_pelanggaran_pdf_template.php'; $html = ob_get_clean();
                        $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
                        $dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
                        $dompdf->stream("Laporan_Pelanggaran_".date('YmdHis').".pdf", ["Attachment" => true]);
                    } elseif ($action === 'generate_laporan_pelanggaran_excel') {
                        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Laporan Pelanggaran Siswa');
                        $headers = ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis Pelanggaran', 'Poin', 'Dicatat Oleh', 'Keterangan'];
                        $sheet->fromArray($headers, NULL, 'A1'); $row_num = 2;
                        foreach ($report_data as $index => $row) {
                            $sheet->setCellValue('A'.$row_num, $index + 1); $sheet->setCellValue('B'.$row_num, date('d-m-Y', strtotime($row['tanggal']))); $sheet->setCellValue('C'.$row_num, $row['nis']); $sheet->setCellValue('D'.$row_num, $row['nama_siswa']); $sheet->setCellValue('E'.$row_num, $row['nama_kelas']); $sheet->setCellValue('F'.$row_num, $row['nama_pelanggaran']); $sheet->setCellValue('G'.$row_num, $row['poin']); $sheet->setCellValue('H'.$row_num, $row['nama_pencatat']); $sheet->setCellValue('I'.$row_num, $row['keterangan']); $row_num++;
                        }
                        foreach (range('A', $sheet->getHighestColumn()) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="Laporan_Pelanggaran_Siswa_'.date('YmdHis').'.xlsx"'); header('Cache-Control: max-age=0');
                        $writer = new Xlsx($spreadsheet); $writer->save('php://output');
                    }
                    exit;
                }
                break;
            case 'laporan_surat_panggilan':
            case 'generate_laporan_surat_panggilan_pdf':
            case 'generate_laporan_surat_panggilan_excel':
                $filter_bulan_sp = $_GET['bulan_sp'] ?? ''; $filter_tahun_sp = $_GET['tahun_sp'] ?? ''; $filter_kelas_sp = $_GET['kelas_id_sp'] ?? ''; $filter_siswa_sp = $_GET['siswa_id_sp'] ?? '';
                $query_report_data = "SELECT spl.*, s.nis, s.nama_lengkap AS nama_siswa, k.nama_kelas, u_ortu.nama_lengkap AS nama_ortu, u_pencatat.nama_lengkap AS nama_pencatat FROM surat_panggilan_log spl JOIN siswa s ON spl.siswa_id = s.id JOIN kelas k ON s.kelas_id = k.id LEFT JOIN users u_ortu ON s.ortu_user_id = u_ortu.id LEFT JOIN users u_pencatat ON spl.dicatat_oleh_user_id = u_pencatat.id WHERE 1=1";
                if (!empty($filter_bulan_sp)) $query_report_data .= " AND MONTH(spl.tanggal_panggilan) = " . (int)$filter_bulan_sp;
                if (!empty($filter_tahun_sp)) $query_report_data .= " AND YEAR(spl.tanggal_panggilan) = " . (int)$filter_tahun_sp;
                if (!empty($filter_kelas_sp)) $query_report_data .= " AND s.kelas_id = " . (int)$filter_kelas_sp;
                if (!empty($filter_siswa_sp)) $query_report_data .= " AND s.id = " . (int)$filter_siswa_sp;
                $query_report_data .= " ORDER BY spl.tanggal_panggilan DESC, s.nama_lengkap ASC";
                $result_report = mysqli_query($koneksi, $query_report_data); $report_data = mysqli_fetch_all($result_report, MYSQLI_ASSOC);
                $filter_tahun = $filter_tahun_sp;
                if ($action === 'generate_laporan_surat_panggilan_pdf' || $action === 'generate_laporan_surat_panggilan_excel'){
                    $pembina_kesiswaan_nama = $_SESSION['nama_lengkap'];
                    if ($action === 'generate_laporan_surat_panggilan_pdf') {
                        $filter_kelas_nama = !empty($filter_kelas_sp) ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id=".(int)$filter_kelas_sp))['nama_kelas'] : 'Semua';
                        $filter_siswa_nama = !empty($filter_siswa_sp) ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_lengkap FROM siswa WHERE id=".(int)$filter_siswa_sp))['nama_lengkap'] : 'Semua';
                        $filter_bulan_nama = !empty($filter_bulan_sp) ? get_bulan_indonesia($filter_bulan_sp) : '';
                        ob_start(); include 'templates/laporan_surat_panggilan_pdf_template.php'; $html = ob_get_clean();
                        $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
                        $dompdf = new Dompdf($options); $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
                        $dompdf->stream("Laporan_Surat_Panggilan_".date('YmdHis').".pdf", ["Attachment" => true]);
                    } elseif ($action === 'generate_laporan_surat_panggilan_excel') {
                        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Laporan Surat Panggilan');
                        $headers = ['No', 'Tanggal Panggilan', 'NIS', 'Nama Siswa', 'Kelas', 'Nama Orang Tua', 'Poin Saat Itu', 'Dicatat Oleh', 'File Surat'];
                        $sheet->fromArray($headers, NULL, 'A1'); $row_num = 2;
                        foreach ($report_data as $index => $row) {
                            $sheet->setCellValue('A'.$row_num, $index + 1); $sheet->setCellValue('B'.$row_num, date('d-m-Y', strtotime($row['tanggal_panggilan']))); $sheet->setCellValue('C'.$row_num, $row['nis']); $sheet->setCellValue('D'.$row_num, $row['nama_siswa']); $sheet->setCellValue('E'.$row_num, $row['nama_kelas']); $sheet->setCellValue('F'.$row_num, $row['nama_ortu']); $sheet->setCellValue('G'.$row_num, $row['poin_saat_panggilan']); $sheet->setCellValue('H'.$row_num, $row['nama_pencatat']); $sheet->setCellValue('I'.$row_num, !empty($row['file_surat']) ? BASE_URL.'/uploads/surat/'.$row['file_surat'] : '-'); $row_num++;
                        }
                        foreach (range('A', $sheet->getHighestColumn()) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="Laporan_Surat_Panggilan_'.date('YmdHis').'.xlsx"'); header('Cache-Control: max-age=0');
                        $writer = new Xlsx($spreadsheet); $writer->save('php://output');
                    }
                    exit;
                }
                break;
            case 'proses_tambah_pengguna':
                $username = $_POST['username']; $password = $_POST['password']; $nama_lengkap = $_POST['nama_lengkap']; $role_baru = $_POST['role'];
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)"); $stmt->bind_param("ssss", $username, $hashed_password, $nama_lengkap, $role_baru);
                if ($stmt->execute()) { header('Location: index.php?action=manajemen_pengguna&status=sukses_tambah_pengguna'); } else { header('Location: index.php?action=manajemen_pengguna&status=gagal_tambah_pengguna'); }
                exit;
            case 'proses_ubah_pengguna':
                $id = $_POST['id']; $username = $_POST['username']; $password = $_POST['password']; $nama_lengkap = $_POST['nama_lengkap']; $role_baru = $_POST['role']; $old_role = $_POST['old_role'];
                $koneksi->begin_transaction();
                try {
                    $query = "UPDATE users SET username=?, nama_lengkap=?";
                    if (!empty($password)) { $hashed_password = password_hash($password, PASSWORD_DEFAULT); $query .= ", password=?"; }
                    if ($id != $_SESSION['user_id']) { $query .= ", role=?"; }
                    $query .= " WHERE id=?";
                    $stmt = $koneksi->prepare($query);
                    if (!empty($password) && $id != $_SESSION['user_id']) { $stmt->bind_param("ssssi", $username, $nama_lengkap, $hashed_password, $role_baru, $id); }
                    elseif (!empty($password)) { $stmt->bind_param("sssi", $username, $nama_lengkap, $hashed_password, $id); }
                    elseif ($id != $_SESSION['user_id']) { $stmt->bind_param("sssi", $username, $nama_lengkap, $role_baru, $id); }
                    else { $stmt->bind_param("ssi", $username, $nama_lengkap, $id); }
                    $stmt->execute();
                    if ($role_baru === 'siswa') {
                        $stmt_siswa_sync = $koneksi->prepare("UPDATE siswa SET nama_lengkap=? WHERE user_id=?"); $stmt_siswa_sync->bind_param("si", $nama_lengkap, $id); $stmt_siswa_sync->execute();
                    } 
                    $koneksi->commit();
                    header('Location: index.php?action=manajemen_pengguna&status=sukses_ubah_pengguna');
                } catch (mysqli_sql_exception $exception) { $koneksi->rollback(); header('Location: index.php?action=manajemen_pengguna&status=gagal_ubah_pengguna'); }
                exit;
            case 'reset_password_pengguna':
                $id = $_GET['id'];
                $stmt_get_username = $koneksi->prepare("SELECT username FROM users WHERE id = ?"); $stmt_get_username->bind_param("i", $id); $stmt_get_username->execute();
                $user_data = $stmt_get_username->get_result()->fetch_assoc(); $stmt_get_username->close();
                if ($user_data) {
                    $new_password_hashed = password_hash($user_data['username'], PASSWORD_DEFAULT);
                    $stmt_update_pass = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?"); $stmt_update_pass->bind_param("si", $new_password_hashed, $id);
                    if ($stmt_update_pass->execute()) { header('Location: index.php?action=manajemen_pengguna&status=sukses_reset_password'); } else { header('Location: index.php?action=manajemen_pengguna&status=gagal_reset_password'); }
                } else { header('Location: index.php?action=manajemen_pengguna&status=gagal_reset_password'); }
                exit;
            case 'proses_ubah_status_izin':
                $id = $_GET['id']; $status_baru = $_GET['status']; $pembina_id = $_SESSION['user_id'];
                $stmt = $koneksi->prepare("UPDATE izin_siswa SET status=?, disetujui_oleh_user_id=?, tanggal_persetujuan=NOW() WHERE id=?"); $stmt->bind_param("sii", $status_baru, $pembina_id, $id);
                if ($stmt->execute()) { header('Location: index.php?action=izin_siswa&status=sukses_ubah_status_izin'); } else { header('Location: index.php?action=izin_siswa&status=gagal_ubah_status_izin'); }
                exit;
            case 'hapus_izin':
                $id = $_GET['id'];
                $stmt_file = $koneksi->prepare("SELECT file_bukti FROM izin_siswa WHERE id=?"); $stmt_file->bind_param("i", $id); $stmt_file->execute(); $file_data = $stmt_file->get_result()->fetch_assoc(); $stmt_file->close();
                $stmt_delete = $koneksi->prepare("DELETE FROM izin_siswa WHERE id=?"); $stmt_delete->bind_param("i", $id);
                if ($stmt_delete->execute()) {
                    if ($file_data && !empty($file_data['file_bukti'])) { unlink("uploads/izin/" . $file_data['file_bukti']); }
                    header('Location: index.php?action=izin_siswa&status=sukses_hapus_izin');
                } else { header('Location: index.php?action=izin_siswa&status=gagal_hapus_izin'); }
                exit;
            case 'hapus_dokumen':
                $id = $_GET['id'];
                $stmt_file = $koneksi->prepare("SELECT file_path FROM dokumen_siswa WHERE id=?"); $stmt_file->bind_param("i", $id); $stmt_file->execute(); $file_data = $stmt_file->get_result()->fetch_assoc(); $stmt_file->close();
                $stmt_delete = $koneksi->prepare("DELETE FROM dokumen_siswa WHERE id=?"); $stmt_delete->bind_param("i", $id);
                if ($stmt_delete->execute()) {
                    if ($file_data && !empty($file_data['file_path'])) { unlink("uploads/dokumen/" . $file_data['file_path']); }
                    header('Location: index.php?action=dokumen_siswa&status=sukses_hapus_dokumen');
                } else { header('Location: index.php?action=dokumen_siswa&status=gagal_hapus_dokumen'); }
                exit;
        }
    } 
    elseif ($role === 'siswa') {
        switch ($action) {
            case 'proses_pengajuan_izin':
                $siswa_id = $_POST['siswa_id']; $jenis_izin = $_POST['jenis_izin']; $tgl_mulai = $_POST['tanggal_izin_mulai']; $tgl_selesai = !empty($_POST['tanggal_izin_selesai']) ? $_POST['tanggal_izin_selesai'] : null; $keterangan = $_POST['keterangan']; $file_bukti_izin = ''; $pengaju_id = $_SESSION['user_id'];
                if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "uploads/izin/"; $file_bukti_izin = uniqid() . '-' . basename($_FILES["file_bukti"]["name"]); $target_file = $target_dir . $file_bukti_izin;
                    if (move_uploaded_file($_FILES["file_bukti"]["tmp_name"], $target_file)) { } else { $file_bukti_izin = ''; }
                }
                $stmt = $koneksi->prepare("INSERT INTO izin_siswa (siswa_id, tanggal_izin_mulai, tanggal_izin_selesai, jenis_izin, keterangan, file_bukti, diajukan_oleh_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $siswa_id, $tgl_mulai, $tgl_selesai, $jenis_izin, $keterangan, $file_bukti_izin, $pengaju_id);
                if ($stmt->execute()) { header('Location: index.php?action=pengajuan_izin&status=sukses_pengajuan_izin'); } else { header('Location: index.php?action=pengajuan_izin&status=gagal_pengajuan_izin'); }
                exit;
            case 'proses_upload_dokumen':
                $siswa_id = $_POST['siswa_id']; $judul_dokumen = $_POST['judul_dokumen']; $jenis_dokumen = $_POST['jenis_dokumen'] ?? null; $deskripsi = $_POST['deskripsi']; $file_dokumen_name = ''; $uploader_id = $_SESSION['user_id'];
                if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "uploads/dokumen/"; $file_dokumen_name = uniqid() . '-' . basename($_FILES["file_dokumen"]["name"]); $target_file = $target_dir . $file_dokumen_name;
                    if (move_uploaded_file($_FILES["file_dokumen"]["tmp_name"], $target_file)) { } else { $file_dokumen_name = ''; }
                }
                $stmt = $koneksi->prepare("INSERT INTO dokumen_siswa (siswa_id, judul_dokumen, deskripsi, jenis_dokumen, file_path, uploaded_by_user_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $siswa_id, $judul_dokumen, $deskripsi, $jenis_dokumen, $file_dokumen_name, $uploader_id);
                if ($stmt->execute()) { header('Location: index.php?action=upload_dokumen_saya&status=sukses_upload_dokumen'); } else { header('Location: index.php?action=upload_dokumen_saya&status=gagal_upload_dokumen'); }
                exit;
            case 'hapus_dokumen_saya':
                $id = $_GET['id']; $current_user_id = $_SESSION['user_id'];
                $stmt_check = $koneksi->prepare("SELECT ds.file_path FROM dokumen_siswa ds JOIN siswa s ON ds.siswa_id = s.id WHERE ds.id = ? AND s.user_id = ?");
                $stmt_check->bind_param("ii", $id, $current_user_id); $stmt_check->execute();
                $file_data = $stmt_check->get_result()->fetch_assoc(); $stmt_check->close();
                if ($file_data) {
                    $stmt_delete = $koneksi->prepare("DELETE FROM dokumen_siswa WHERE id=?"); $stmt_delete->bind_param("i", $id);
                    if ($stmt_delete->execute()) {
                        if (!empty($file_data['file_path'])) { unlink("uploads/dokumen/" . $file_data['file_path']); }
                        header('Location: index.php?action=daftar_dokumen_saya&status=sukses_hapus_dokumen');
                    } else { header('Location: index.php?action=daftar_dokumen_saya&status=gagal_hapus_dokumen'); }
                } else { header('Location: index.php?action=daftar_dokumen_saya&status=gagal_hapus_dokumen'); }
                exit;
        }
    }
}

if (!isset($_SESSION['is_logged_in'])) { header('Location: login.php'); exit; }
if ($action === 'logout') { session_destroy(); header('Location: login.php'); exit; }

include 'templates/header.php';
$role = $_SESSION['role'];
if ($role === 'pembina') { include 'templates/sidebar_pembina.php'; }
elseif ($role === 'siswa') { include 'templates/sidebar_siswa.php'; }
elseif ($role === 'orang_tua') { include 'templates/sidebar_ortu.php'; }

$page_file = '';
$current_action = $_GET['action'] ?? 'dashboard';

if ($role === 'pembina') {
    $allowed_pembina_pages = [
        'data_siswa', 'tambah_siswa', 'ubah_siswa',
        'manajemen_kelas', 'tambah_kelas', 'ubah_kelas',
        'prestasi_siswa', 'tambah_prestasi_siswa', 'jenis_prestasi', 'form_jenis_prestasi',
        'pelanggaran_siswa', 'tambah_pelanggaran_siswa', 'jenis_pelanggaran', 'form_jenis_pelanggaran',
        'surat_panggilan',
        'tambah_agenda_kesiswaan', 'ubah_agenda_kesiswaan',
        'laporan_prestasi', 'laporan_pelanggaran', 'laporan_surat_panggilan',
        'manajemen_pengguna', 'tambah_pengguna', 'ubah_pengguna',
        'izin_siswa', 'dokumen_siswa'
    ];
    if (in_array($current_action, $allowed_pembina_pages)) {
        $page_file = "pages/pembina/{$current_action}.php";
    }
} elseif ($role === 'siswa') {
    $allowed_siswa_pages = [
        'pengajuan_izin', 'riwayat_izin_saya',
        'upload_dokumen_saya', 'daftar_dokumen_saya'
    ];
    if (in_array($current_action, $allowed_siswa_pages)) {
        $page_file = "pages/siswa/{$current_action}.php";
    }
} elseif ($role === 'orang_tua') {
    $allowed_ortu_pages = ['riwayat_izin_anak', 'dokumen_anak'];
    if (in_array($current_action, $allowed_ortu_pages)) {
        $page_file = "pages/orang_tua/{$current_action}.php";
    }
}

if ($current_action === 'agenda_kesiswaan') {
    if ($role === 'pembina') {
        $page_file = "pages/pembina/agenda_kesiswaan.php";
    } else {
        $page_file = "pages/siswa/agenda_kesiswaan.php";
    }
} elseif (empty($page_file) && $current_action === 'dashboard') {
     switch ($role) {
        case 'pembina': $page_file = 'pages/pembina/dashboard.php'; break;
        case 'siswa': $page_file = 'pages/siswa/dashboard.php'; break;
        case 'orang_tua': $page_file = 'pages/orang_tua/dashboard.php'; break;
    }
}

if (!empty($page_file) && file_exists($page_file)) {
    include $page_file;
} else {
    $fallback_page = 'pages/' . $role . '/dashboard.php';
    if(file_exists($fallback_page)){
        include $fallback_page;
    } else {
        include 'pages/404.php';
    }
}

include 'templates/footer.php';
?>