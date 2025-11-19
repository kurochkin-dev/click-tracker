<script lang="ts">
  export let dateFrom: string | null = null;
  export let dateTo: string | null = null;

  let localDateFrom = dateFrom || '';
  let localDateTo = dateTo || '';

  let lastSyncedFrom = dateFrom;
  let lastSyncedTo = dateTo;

  $: if (dateFrom !== lastSyncedFrom) {
    localDateFrom = dateFrom || '';
    lastSyncedFrom = dateFrom;
  }

  $: if (dateTo !== lastSyncedTo) {
    localDateTo = dateTo || '';
    lastSyncedTo = dateTo;
  }

  function updateFrom() {
    dateFrom = localDateFrom || null;
  }

  function updateTo() {
    dateTo = localDateTo || null;
  }
</script>

<div class="date-range-picker">
  <div class="date-input-group">
    <label for="date-from">From:</label>
    <input
      id="date-from"
      type="date"
      bind:value={localDateFrom}
      on:input={updateFrom}
      class="date-input"
    />
  </div>
  <div class="date-input-group">
    <label for="date-to">To:</label>
    <input
      id="date-to"
      type="date"
      bind:value={localDateTo}
      on:input={updateTo}
      class="date-input"
    />
  </div>
</div>

<style>
  .date-range-picker {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .date-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .date-input-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
  }

  .date-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s;
  }

  .date-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
</style>

