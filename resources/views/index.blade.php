@extends('layouts.master')

@section('description', 'Welcome to Vancouver - located in the left of Canada on the VATSIM network!')

@section('content')
    <link rel="stylesheet" type="text/css" href="{{ asset('/css/home.css') }}" />

    {{-- Hero --}}
    <div class="hero-section">
        <div class="hero-bg" style="background-image: url({{ $background->url }})"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="row align-items-center" style="min-height: calc(100vh - 60px);">
                <div class="col-lg-6 hero-left">
                    <div class="hero-eyebrow">
                        @if(count($finalPositions) > 0)
                            <span class="hero-online-dot"></span>
                            <span>{{ count($finalPositions) }} controller{{ count($finalPositions) > 1 ? 's' : '' }} online now</span>
                        @else
                            <span class="hero-offline-dot"></span>
                            <span>No controllers online</span>
                        @endif
                    </div>
                    <h1 class="hero-title">From Sea<br>to Sky.</h1>
                    <p class="hero-subtitle">Canada's West Coast on the VATSIM network — Vancouver FIR provides ATC from the Pacific coast to the Rockies.</p>
                    <div class="hero-ctas">
                        <a href="{{ route('booking') }}" class="btn btn-hero-primary">
                            <i class="fas fa-calendar-check mr-2"></i>View Bookings
                        </a>
                        <a href="{{ route('roster.public') }}" class="btn btn-hero-secondary">
                            <i class="fas fa-users mr-2"></i>Our Roster
                        </a>
                    </div>
                    <small class="hero-credit">Photo by {{ $background->credit }}</small>
                </div>
            </div>
        </div>
        <a href="#homepage-content" class="hero-scroll-cue" aria-label="Scroll down">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>

    {{-- Content panels --}}
    <div class="hp-wrap container-fluid px-0" id="homepage-content">
        <div class="container hp-container">

            {{-- Live ATC strip --}}
            <div class="hp-live-bar">
                @if(count($finalPositions) > 0)
                    <span class="hp-live-dot"></span>
                    <span class="hp-live-word">Live</span>
                    @foreach($finalPositions as $p)
                        <span class="hp-ctrl-chip">
                            <a href="{{ url('/roster/'.$p->cid) }}" class="hp-ctrl-link">
                                <b>{{ $p->callsign }}</b>&nbsp;·&nbsp;{{ $p->name == $p->cid ? $p->name : $p->name }}
                            </a>
                        </span>
                        @if(!$loop->last)<span class="hp-sep">·</span>@endif
                    @endforeach
                @else
                    <span class="hp-offline-dot"></span>
                    <span class="hp-live-word hp-live-word--off">No ATC online</span>
                    <span class="hp-ctrl-chip"><a href="{{ route('booking') }}" class="hp-ctrl-link">Check upcoming bookings</a></span>
                @endif
            </div>

            {{-- Main split: events + news --}}
            <div class="hp-main-grid">
                {{-- Left: featured event + upcoming --}}
                <div class="hp-panel">
                    <span class="hp-panel-label">Featured event</span>
                    @if(count($nextEvents) > 0)
                        @php
                            $featured = $nextEvents->first();
                            $fStart   = \Carbon\Carbon::parse($featured->start_timestamp);
                            $fEnd     = \Carbon\Carbon::parse($featured->end_timestamp);
                            $fLive    = \Carbon\Carbon::now()->between($fStart, $fEnd);
                        @endphp
                        <a href="{{ url('/events/'.$featured->slug) }}" class="hp-feat-title">{{ $featured->name }}</a>
                        <div class="hp-feat-meta">
                            <span><i class="fas fa-calendar-alt"></i> {{ $fStart->format('D M j · Hi') }}–{{ $fEnd->format('Hi') }}z</span>
                        </div>
                        @if($fLive)
                            <span class="hp-badge hp-badge--live">Happening now</span>
                        @else
                            <a href="{{ url('/events/'.$featured->slug) }}" class="hp-text-link">Details <i class="fas fa-arrow-right"></i></a>
                        @endif
                    @else
                        <p class="hp-empty">No upcoming events — check back soon.</p>
                    @endif

                    <div class="hp-inner-divider"></div>
                    <span class="hp-panel-label">Upcoming</span>

                    @if(count($nextEvents) > 1)
                        <div class="hp-mini-events">
                            @foreach($nextEvents->skip(1)->take(3) as $e)
                                @php $eStart = \Carbon\Carbon::parse($e->start_timestamp); @endphp
                                <div class="hp-mini-row">
                                    <a href="{{ url('/events/'.$e->slug) }}" class="hp-mini-name">{{ $e->name }}</a>
                                    <span class="hp-mini-date">{{ $eStart->format('M j') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="hp-empty">No further events scheduled.</p>
                    @endif
                </div>

                {{-- Right: news --}}
                <div class="hp-panel hp-panel--right">
                    <span class="hp-panel-label">
                        News
                        <a href="{{ url('/news') }}" class="hp-panel-all">All news <i class="fas fa-arrow-right"></i></a>
                    </span>
                    @if(count($news) > 0)
                        <div class="hp-news-list">
                            @foreach($news->take(4) as $n)
                                <div class="hp-news-row">
                                    <span class="hp-news-date">{{ $n->posted_on_pretty() }}</span>
                                    <a href="{{ url('/news/'.$n->slug) }}" class="hp-news-hed">{{ $n->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="hp-empty">No recent news.</p>
                    @endif
                </div>
            </div>

            {{-- Bottom split: controllers + weather --}}
            <div class="hp-bottom-grid">
                {{-- Controllers leaderboard --}}
                <div class="hp-panel">
                    <span class="hp-panel-label">Top controllers — {{ \Carbon\Carbon::now()->format('F') }}</span>
                    @php $maxTime = collect($topControllersArray)->max('minutes') ?: 1; @endphp
                    @if(count($topControllersArray) > 0)
                        <div class="hp-ldr-list">
                            @foreach(collect($topControllersArray)->where('time', '!=', 0)->take(4) as $i => $t)
                                <div class="hp-ldr-row">
                                    <span class="hp-ldr-rank {{ $i === 0 ? 'hp-ldr-rank--gold' : '' }}">{{ $i + 1 }}</span>
                                    <span class="hp-ldr-name">
                                        {{ \App\Models\Users\User::find($t['cid'])?->fullName('F') ?? '—' }}
                                    </span>
                                    <div class="hp-ldr-bar-wrap">
                                        <div class="hp-ldr-bar" style="width:{{ round(($t['minutes'] / $maxTime) * 100) }}%"></div>
                                    </div>
                                    <span class="hp-ldr-time">{{ $t['time'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="hp-empty">No data yet this month.</p>
                    @endif
                </div>

                {{-- Weather --}}
                <div class="hp-panel hp-panel--right">
                    <span class="hp-panel-label">Weather</span>
                    @if(count($weather) > 0)
                        <div class="hp-wx-list">
                            @foreach($weather as $w)
                                <div class="hp-wx-row">
                                    <span class="hp-wx-icao">{{ $w->icao }}</span>
                                    <span class="hp-wx-cat hp-wx-{{ strtolower($w->flight_category) }}">{{ $w->flight_category }}</span>
                                    <span class="hp-wx-metar">{{ $w->raw_text }}</span>
                                    @if(\Carbon\Carbon::make($w->observed) < \Carbon\Carbon::now()->subHours(2))
                                        <span class="hp-wx-old">OLD</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="hp-empty">No weather data available.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
@stop
