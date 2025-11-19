<script lang="ts">
  import type { CampaignStats } from '../types';
  import CampaignCard from './CampaignCard.svelte';

  export let campaigns: CampaignStats[] = [];
</script>

<div class="campaign-list">
  {#if campaigns.length === 0}
    <div class="empty-state">
      <p>No campaigns found</p>
    </div>
  {:else}
    <div class="campaigns-grid">
      {#each campaigns as campaign (campaign.campaign_id)}
        <CampaignCard {campaign} />
      {/each}
    </div>
  {/if}
</div>

<style>
  .campaign-list {
    margin-top: 1.5rem;
  }

  .campaigns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  .empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
  }
</style>

