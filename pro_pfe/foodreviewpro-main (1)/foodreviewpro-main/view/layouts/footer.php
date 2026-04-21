  </main>
  <!-- /PAGE CONTENT -->

</div>
<!-- /MAIN WRAPPER -->

<!-- Third-party JS -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Project JS -->
<script src="/foodreviewpro/public/js/script.js"></script>
<script src="/foodreviewpro/public/js/charts.js"></script>
<script src="/foodreviewpro/public/js/tables.js"></script>

<script>
  // Mobile sidebar toggle
  document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024)
      document.getElementById('sidebar').classList.remove('open');
  });
</script>

</body>
</html>
