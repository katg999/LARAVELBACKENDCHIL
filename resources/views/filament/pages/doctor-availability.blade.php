<x-filament::page>
    <x-filament::card>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Available Doctors</h2>
                {{ $this->headerActions }}
            </div>
            
            @foreach($availableDoctors as $specialization => $doctors)
                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b">
                        <h3 class="font-medium">{{ $specialization }}</h3>
                    </div>
                    <div class="divide-y">
                        @foreach($doctors as $doctor)
                            <div class="px-4 py-3 flex justify-between items-center">
                                <div>
                                    <p class="font-medium">{{ $doctor->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $doctor->email }}</p>
                                </div>
                                <div>
                                    <x-filament::button 
                                        wire:click="$dispatch('open-modal', { id: 'book-appointment', component: 'book-appointment', arguments: { doctor_id: {{ $doctor->id }} } })">
                                        Book Appointment
                                    </x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::card>
    
    <x-filament::modal id="book-appointment">
        @livewire('book-appointment')
    </x-filament::modal>
</x-filament::page>