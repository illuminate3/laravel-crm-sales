{{--
    Sales Module Master Layout
    This layout provides backward compatibility while using the modern component-based approach
--}}
<x-admin::layouts>
    <x-slot:title>
        @yield('title')
    </x-slot>

    @yield('content')
</x-admin::layouts>
