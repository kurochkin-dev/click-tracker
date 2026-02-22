<script lang="ts">
  import type { GoodStats } from '../types';
  import GoodCard from './GoodCard.svelte';

  export let goods: GoodStats[];
</script>

<div class="good-list">
  {#each goods as good (good.good_id)}
    <GoodCard {good} />
  {:else}
    <p class="empty">Данных пока нет</p>
  {/each}
</div>

<style>
  .good-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
  }

  .empty {
    color: #9ca3af;
    text-align: center;
    padding: 2rem;
    grid-column: 1 / -1;
  }
</style>
