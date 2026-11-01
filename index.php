<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Material Design for Bootstrap</title>
    <link rel="icon" href="img/mdb-favicon.ico" type="image/x-icon" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
    />
    <link rel="stylesheet" href="css/mdb.min.css" />
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  </head>

  <body>
  <section class="vh-100 animated-background">
      <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
          <div class="col col-xl-10">
            <div class="card" style="border-radius: 1rem;">
              <div class="row g-0">
                <div class="col-md-6 col-lg-5 d-none d-md-block">
                  <img src="img/cane.jpg"
                    alt="login form" class="img-fluid" style="border-radius: 1rem 0 0 1rem;" />
                </div>
                <div class="col-md-6 col-lg-7 d-flex align-items-center">
                  <div class="card-body p-4 p-lg-5 text-black">
                    <form>
    
                      <div class="d-flex align-items-center mb-4 pb-1">
                      <img src="img/logo.jpg" 
                        alt="Logo PT. INTERMULTICON KRISTAL SENTRA" 
                        class="me-3" style="width: 70px; height: 40px;">
                        <span class="h5 fw-bold mb-0">PT. INTERMULTICON KRISTAL SENTRA</span>
                      </div>
    
                      <h5 class="fw-normal mb-1 pb-1" style="letter-spacing: 1px;">Welcome to Reimbustment!</h5>
                      <h5 class="fw-normal mb-3 pb-3 small text-muted" style="letter-spacing: 0.5px;">Sign into your account to continue</h5>

                      
                      <div data-mdb-input-init class="form-outline mb-4">
                        <input type="email" id="form2Example17" class="form-control form-control-lg" required />
                        <label class="form-label" for="form2Example17">Email address</label>
                        <span id="emailTooltip" class="tooltip-message">Email Required!</span>
                      </div>
    
                      <div data-mdb-input-init class="form-outline mb-2">
                        <input type="password" id="form2Example27" class="form-control form-control-lg" required />
                        <label class="form-label" for="form2Example27">Password</label>
                        <i id="togglePassword" class="fa fa-eye" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer;"></i>
                        <span id="passwordTooltip" class="tooltip-message">Password Required!</span>
                      </div>

                      <!-- Pesan error di bawah input password, di luar box -->
                      <div id="loginErrorMessage" class="error-message" style="display: none; color: red; font-size: 12px;">
                        Your email or password is wrong
                      </div>
    
                      <div class="pt-3 mb-4">
                      <button id="loginButton" data-mdb-button-init data-mdb-ripple-init class="btn btn-lg btn-block btn-gradient" 
                        style="background-color: #16423C; color: white;" type="button">Login</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <script type="text/javascript" src="js/mdb.umd.min.js"></script>
    <script type="text/javascript" src="js/custom.js"></script>

    <script>
    // Menangani login saat tombol login diklik
    document.getElementById('loginButton').addEventListener('click', function() {
      // Ambil nilai email dan password dari input
      const email = document.getElementById('form2Example17').value;
      const password = document.getElementById('form2Example27').value;

      // Validasi input (bisa ditambahkan sesuai kebutuhan)
      if (email === '' || password === '') {
        alert('Email dan password harus diisi!');
        return;
      }

      // Mengirim data login ke backend menggunakan Fetch API
      fetch('login.php', {
        method: 'POST',
        body: JSON.stringify({
          email: email,
          password: password
        }),
        headers: {
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())  // Parsing respons JSON dari backend
      .then(data => {
        if (data.status === 'success') {
          // Jika login berhasil, arahkan pengguna ke halaman yang sesuai
          window.location.href = data.redirect;  // Redirect sesuai dengan role (dashboard_admin.php atau dashboard_user.php)
        } else {
          // Jika login gagal, tampilkan pesan error
          document.getElementById('loginErrorMessage').style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    });
    </script>
  </body>
</html>
