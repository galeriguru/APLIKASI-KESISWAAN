<?php
session_start();
if (isset($_SESSION['is_logged_in'])) {
    header('Location: index.php');
    exit;
}
$error = isset($_GET['error']) ? "Username atau password salah!" : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Aplikasi Kesiswaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <div class="login-header">
                <i class="fas fa-user-graduate"></i>
                <h1>Aplikasi Kesiswaan</h1>
                <p>Silakan masuk untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form action="index.php?action=process_login" method="POST" id="loginForm">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required />
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required />
                </div>
                <button type="submit" class="btn-login">Masuk</button>
            </form>

            <div class="forgot-password">
                <a href="#" onclick="showForgotPassword()">Lupa password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showForgotPassword() {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title text-primary">
                                    <i class="fas fa-key me-2"></i>Lupa Password
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                                <p>Silakan hubungi administrator untuk reset password.</p>
                            </div>
                            <div class="modal-footer border-0 justify-content-center">
                                <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
            bsModal.show();
            document.getElementById('forgotPasswordModal').addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }
    </script>
</body>
</html>
