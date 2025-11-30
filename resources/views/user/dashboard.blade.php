<x-layout>
    <x-navbar module="Dashboard">
        <x-slot:sidebar>
            <x-sidebar-menu-item 
                href="{{ route('user.dashboard') }}" 
                :active="request()->routeIs('user.dashboard')"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>'
            >
                Dashboard
            </x-sidebar-menu-item>
            
            <x-sidebar-menu-dropdown 
                label="Trucks"
                icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8M9 12h6" /></svg>'
            >
                <x-sidebar-menu-item 
                    href="#" 
                    :active="false"
                    icon='<img src="https://cdn-icons-png.flaticon.com/512/9331/9331979.png" alt="Incoming" class="w-5 h-5 object-contain" />'
                >
                    Incoming Trucks
                </x-sidebar-menu-item>
                <x-sidebar-menu-item 
                    href="#" 
                    :active="false"
                    icon='<img src="https://cdn-icons-png.flaticon.com/512/7468/7468319.png" alt="Outgoing" class="w-5 h-5 object-contain" />'
                >
                    Outgoing Trucks
                </x-sidebar-menu-item>
                <x-sidebar-menu-item 
                    href="#" 
                    :active="false"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
                >
                    Completed Trucks
                </x-sidebar-menu-item>
            </x-sidebar-menu-dropdown>
        </x-slot:sidebar>
    </x-navbar>
</x-layout>