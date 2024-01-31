<footer class="footer position-absolute bottom-2 py-2 w-100">
        
      </footer>
    </div>
  </main>
  <!--   Core JS Files   -->
  <script src="<?php echo get_template_directory_uri(); ?>/assets/js/core/popper.min.js"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/assets/js/core/bootstrap.min.js"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="<?php echo get_template_directory_uri(); ?>/assets/js/plugins/smooth-scrollbar.min.js"></script>
  <!-- <script src="/assets/js/plugins/flatpickr.min.js"></script> -->
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->

  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="<?php echo get_template_directory_uri(); ?>/assets/js/material-dashboard.min.js?v=3.1.0"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>

</html>