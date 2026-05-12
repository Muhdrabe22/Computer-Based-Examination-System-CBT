  </div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /dashboard-layout -->

<script>
// Live clock
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent =
    now.toLocaleTimeString('en-NG', { hour12: false });
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>
