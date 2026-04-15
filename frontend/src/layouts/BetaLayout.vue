<template>
  <div class="flex flex-col h-screen bg-white dark:bg-gray-950">
    <header class="flex items-center h-12 px-4 border-b border-gray-200 dark:border-gray-800">
      <span class="font-medium text-brand-600">Papyrus</span>
      <span class="ml-3 text-xs text-gray-500 bg-gray-100 dark:bg-gray-800 rounded px-2 py-0.5">
        Lecture bêta
      </span>
    </header>
    <main class="flex-1 overflow-auto">
      <RouterView />
    </main>
  </div>
</template>
