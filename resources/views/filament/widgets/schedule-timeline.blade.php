<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold" style="color: #ffffff ;">Schedule Timeline - {{ $displayDate }}</h2>
                <div class="flex gap-2">
                    <x-filament::button 
                        wire:click="switchToToday"
                        :color="$selectedDate === 'today' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        Today
                    </x-filament::button>
                    <x-filament::button 
                        wire:click="switchToTomorrow"
                        :color="$selectedDate === 'tomorrow' ? 'primary' : 'gray'"
                        size="sm"
                    >
                        Tomorrow
                    </x-filament::button>
                </div>
            </div>
            
            <!-- Timeline Container -->
            <div class="bg-white rounded-lg border overflow-x-auto">
                <!-- Time Header -->
                <div class="flex border-b bg-gray-50">
                    <div class="w-32 p-3 font-semibold border-r" style="color: #000000 !important;">Afspraken</div>
                    <div class="flex-1 flex">
                        @for($hour = 9; $hour <= 18; $hour++)
                            <div class="flex-1 p-2 text-center text-sm font-medium border-r" style="color: #000000 !important;">
                                {{ sprintf('%02d:00', $hour) }}
                            </div>
                        @endfor
                    </div>
                </div>
                
                <!-- schema Nick -->
                <div class="flex border-b relative h-16">
                    <div class="w-32 p-3 font-medium bg-blue-50 border-r flex items-center">
                        <span style="color: #1e40af !important;">👨‍💼 Nick</span>
                    </div>
                    <div class="flex-1 relative bg-gray-50">
                        <!-- Hour grid lines -->
                        @for($hour = 9; $hour <= 18; $hour++)
                            <div class="absolute top-0 bottom-0 border-r border-gray-200" 
                                 style="left: {{ (($hour - 9) / 9) * 100 }}%"></div>
                        @endfor
                        
                        <!-- afspraken nick-->
                        @foreach($maleBookings as $booking)
                            @php
                                $startTime = \Carbon\Carbon::parse($booking->date);
                                $endTime = \Carbon\Carbon::parse($booking->end_time);
                                $startHour = $startTime->hour + ($startTime->minute / 60);
                                $duration = $startTime->diffInMinutes($endTime) / 60;
                                
                                $startPercent = (($startHour - 9) / 9) * 100;
                                $widthPercent = ($duration / 9) * 100;
                            @endphp
                            <div class="absolute text-xs px-2 py-1 rounded shadow-md top-2 bottom-2 overflow-hidden"
                                 style="left: {{ $startPercent }}%; width: {{ $widthPercent }}%; background-color: #2563eb; border: 1px solid #1d4ed8;">
                                <div style="font-weight: bold; color: #ffffff;">{{ $booking->name }}</div>
                                <div style="color: #dbeafe;">{{ $booking->services->first()->name ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- schema Vicky -->
                <div class="flex relative h-16">
                    <div class="w-32 p-3 font-medium bg-pink-50 border-r flex items-center">
                        <span style="color: #be185d !important;">👩‍💼 Vicky</span>
                    </div>
                    <div class="flex-1 relative bg-gray-50">
                        <!-- Hour grid lines -->
                        @for($hour = 9; $hour <= 18; $hour++)
                            <div class="absolute top-0 bottom-0 border-r border-gray-200" 
                                 style="left: {{ (($hour - 9) / 9) * 100 }}%"></div>
                        @endfor
                        
                        <!-- afspraken vicky-->
                        @foreach($femaleBookings as $booking)
                            @php
                                $startTime = \Carbon\Carbon::parse($booking->date);
                                $endTime = \Carbon\Carbon::parse($booking->end_time);
                                $startHour = $startTime->hour + ($startTime->minute / 60);
                                $duration = $startTime->diffInMinutes($endTime) / 60;
                                
                                $startPercent = (($startHour - 9) / 9) * 100;
                                $widthPercent = ($duration / 9) * 100;
                            @endphp
                            <div class="absolute text-xs px-2 py-1 rounded shadow-md top-2 bottom-2 overflow-hidden"
                                 style="left: {{ $startPercent }}%; width: {{ $widthPercent }}%; background-color: #db2777; border: 1px solid #be185d;">
                                <div style="font-weight: bold; color: #ffffff;">{{ $booking->name }}</div>
                                <div style="color: #fce7f3;">{{ $booking->services->first()->name ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="flex gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-500 rounded"></div>
                    <span>Afspraken Nick</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-pink-500 rounded"></div>
                    <span>Afspraken Vicky</span>
                </div>
                <div class="text-gray-600">
                    <strong> als er niets staat... breaktime!</strong>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>