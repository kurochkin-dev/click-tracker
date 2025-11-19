<script lang="ts">
  import type { CampaignStats } from '../types';
  import { formatPercent, formatNumber } from '../utils/date';

  export let campaign: CampaignStats;
</script>

<div class="campaign-card">
  <div class="campaign-header">
    <h3 class="campaign-id">{campaign.campaign_id}</h3>
    <span class="ctr-badge">CTR: {formatPercent(campaign.ctr)}</span>
  </div>
  <div class="campaign-stats">
    <div class="stat-item">
      <span class="stat-label">Clicks</span>
      <span class="stat-value">{formatNumber(campaign.clicks)}</span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Impressions</span>
      <span class="stat-value">{formatNumber(campaign.impressions)}</span>
    </div>
    <div class="stat-item">
      <span class="stat-label">Unique Users</span>
      <span class="stat-value">{formatNumber(campaign.unique_users)}</span>
    </div>
  </div>
</div>

<style>
  .campaign-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s;
  }

  .campaign-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .campaign-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
  }

  .campaign-id {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .ctr-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .campaign-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }

  .stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
  }
</style>

