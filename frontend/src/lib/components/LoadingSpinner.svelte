<div class="spinner-container">
  <div class="spinner"></div>
  <p class="spinner-text">Loading...</p>
</div>

<style>
  .spinner-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    gap: 1rem;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  .spinner-text {
    color: #6b7280;
    font-size: 0.875rem;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
</style>

