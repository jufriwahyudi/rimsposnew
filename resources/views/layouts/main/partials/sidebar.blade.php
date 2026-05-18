<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div class="logo-icon">
            <img src="{{ asset('assets/images/alazca_logo.png')}}" class="logo-img" alt="">
        </div>
        <div class="logo-name flex-grow-1">
            <h5 class="mb-0">{{ config('app.name') }}</h5>
        </div>
        <div class="sidebar-close">
            <span class="material-icons-outlined">close</span>
        </div>
    </div>
    <div class="sidebar-nav">
        <!--navigation-->
        <ul class="metismenu" id="sidenav">
            @if(isset($menucache))
            @foreach($menucache as $menu)
            @php
            $hasChild = '';
            if($menu->children->count() > 0){
            $hasChild = 'class=has-arrow';
            }
            @endphp
            <li>
                <a {{ $menu->routename === '#' ? 'href=javascript:;' : 'href='.route($menu->routename).'' }} {{$hasChild}}>
                    <div class="parent-icon"><i class="material-icons-outlined">{{ $menu->icon }}</i></div>
                    <div class="menu-title">{{ $menu->nama }}</div>
                </a>
                @if($menu->children->isNotEmpty())
                <ul>
                    @foreach ($menu->children as $child)
                    <li><a href="{{ ($child->routename === '#' ? 'javascript:void(0);' : route($child->routename)) }}"><i class="material-icons-outlined">arrow_right</i> {{ $child->nama }}</a></li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
            @endif

        </ul>
        <!--end navigation-->
    </div>
</aside>
<!--end sidebar-->
