<script lang="ts">
  import '../app.css';
</script>

<div class="app-container">
  <header class="app-header">
    <h1>Click Tracker</h1>
    <p class="subtitle">Analytics Dashboard</p>
  </header>
  <main class="app-main">
    <slot />
  </main>
</div>

<style>
  .app-container {
    min-height: 100vh;
    background: #f9fafb;
  }

  .app-header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem 2rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .app-header h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: #111827;
  }

  .subtitle {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: #6b7280;
  }

  .app-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
  }
</style>

