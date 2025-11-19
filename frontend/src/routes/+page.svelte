<script lang="ts">
  import { onMount } from 'svelte';
  import { browser } from '$app/environment';
  import { apiClient } from '$lib/api/client';
  import type { AllCampaignsReport, DailyReport } from '$lib/types';
  import { getDefaultDateRange } from '$lib/utils/date';
  import DateRangePicker from '../lib/components/DateRangePicker.svelte';
  import CampaignList from '../lib/components/CampaignList.svelte';
  import StatsChart from '../lib/components/StatsChart.svelte';
  import LoadingSpinner from '../lib/components/LoadingSpinner.svelte';

  let loading = true;
  let error: string | null = null;
  let campaignsReport: AllCampaignsReport | null = null;
  let dailyReport: DailyReport | null = null;

  const defaultRange = getDefaultDateRange();
  let dateFrom: string | null = defaultRange.from;
  let dateTo: string | null = defaultRange.to;
  let isMounted = false;

  async function loadData() {
    if (!browser || !isMounted) return;

    loading = true;
    error = null;

    try {
      [campaignsReport, dailyReport] = await Promise.all([
        apiClient.getAllCampaignsReport(dateFrom, dateTo),
        apiClient.getDailyReport(dateFrom, dateTo)
      ]);
    } catch (e) {
      error = e instanceof Error ? e.message : 'Failed to load data';
      console.error('Error loading data:', e);
    } finally {
      loading = false;
    }
  }

  $: if (browser && isMounted && (dateFrom !== null || dateTo !== null)) {
    loadData();
  }

  onMount(() => {
    isMounted = true;
    loadData();
  });
</script>

<svelte:head>
  <title>Click Tracker - Dashboard</title>
</svelte:head>

<div class="dashboard">
  <div class="dashboard-header">
    <h2>Dashboard</h2>
    <DateRangePicker bind:dateFrom bind:dateTo />
  </div>

  {#if loading}
    <LoadingSpinner />
  {:else if error}
    <div class="error-message">
      <p>Error: {error}</p>
      <button on:click={loadData} class="retry-button">Retry</button>
    </div>
  {:else}
    {#if campaignsReport && dailyReport}
      <div class="dashboard-content">
        <div class="stats-section">
          <h3>Total Statistics</h3>
          <div class="total-stats">
            <div class="total-stat-item">
              <span class="total-stat-label">Total Clicks</span>
              <span class="total-stat-value">{campaignsReport.total.clicks.toLocaleString()}</span>
            </div>
            <div class="total-stat-item">
              <span class="total-stat-label">Total Impressions</span>
              <span class="total-stat-value">
                {campaignsReport.total.impressions.toLocaleString()}
              </span>
            </div>
            <div class="total-stat-item">
              <span class="total-stat-label">Unique Users</span>
              <span class="total-stat-value">
                {campaignsReport.total.unique_users.toLocaleString()}
              </span>
            </div>
          </div>
        </div>

        <div class="chart-section">
          <h3>Daily Statistics</h3>
          <div class="chart-wrapper">
            <StatsChart data={dailyReport.daily} type="line" title="Daily Events" />
          </div>
        </div>

        <div class="campaigns-section">
          <h3>Campaigns ({campaignsReport.campaigns.length})</h3>
          <CampaignList campaigns={campaignsReport.campaigns} />
        </div>
      </div>
    {/if}
  {/if}
</div>

<style>
  .dashboard {
    width: 100%;
  }

  .dashboard-header {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
  }

  .dashboard-header h2 {
    margin: 0 0 1rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
  }

  .dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .stats-section,
  .chart-section,
  .campaigns-section {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .stats-section h3,
  .chart-section h3,
  .campaigns-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
  }

  .total-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .total-stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.375rem;
  }

  .total-stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .total-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
  }

  .chart-wrapper {
    margin-top: 1rem;
  }

  .error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    color: #991b1b;
  }

  .retry-button {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
  }

  .retry-button:hover {
    background: #2563eb;
  }
</style>

