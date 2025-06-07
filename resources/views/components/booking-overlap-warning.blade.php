<div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-lg p-4 mb-4 w-full">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">Booking Overlap Detected</h3>
            <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                {!! $warningMessage !!}
            </div>
        </div>
    </div>
</div> 