<?php

namespace App\Http\Controllers;

use App\Models\Events\Event;
use App\Models\Network\SessionLog;
use App\Models\News\News;
use App\Models\Settings\HomepageImages;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function view(): View
    {
        // VATSIM Controllers (from cache)
        $finalPositions = Cache::get('vatsim.controllers', []);

        // Weather (from cache)
        $weather = Cache::get('weather.data', []);

        try {
            $banner = DB::table('core_info')->first()
                ?? (object)['banner' => '', 'bannerLink' => '', 'bannerMode' => ''];
        } catch (Exception $e) {
            \Log::error('Failed to fetch banner: '.$e->getMessage());
            $banner = (object)['banner' => '', 'bannerLink' => '', 'bannerMode' => ''];
        }

        // Load cached data or default to empty collections
        $finalPositions = Cache::get('vatsim.controllers', []);
        $news = Cache::get('home.news', collect());
        $nextEvents = Cache::get('home.events', collect());
        $topControllersArray = Cache::get('home.topControllers', []);
        $weather = Cache::get('weather.data', []);
        $background = Cache::get('home.background', null);

        // Background image
        if (! $background) {
            try {
                $background = HomepageImages::inRandomOrder()->first();
            } catch (Exception $e) {
                \Log::error('Failed to fetch background image: '.$e->getMessage());
            }
        }
        if (! $background) {
            $background = (object)['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Vancouver_skyline.jpg/1280px-Vancouver_skyline.jpg', 'credit' => ''];
        }

        // News (cache for 5 min)
        if ($news->isEmpty()) {
            try {
                $news = News::where('visible', true)
                    ->orderBy('published', 'desc')
                    ->take(3)
                    ->get();
                Cache::put('home.news', $news, 300);
            } catch (Exception $e) {
                \Log::error('Failed to fetch news: '.$e->getMessage());
            }
        }

        // Upcoming events (cache for 5 min)
        if ($nextEvents->isEmpty()) {
            try {
                $nextEvents = Event::where('end_timestamp', '>', now())
                    ->orderBy('end_timestamp')
                    ->take(3)
                    ->get();
                Cache::put('home.events', $nextEvents, 300);
            } catch (Exception $e) {
                \Log::error('Failed to fetch events: '.$e->getMessage());
            }
        }

        // Top Controllers (cache for 15 min)
        if (empty($topControllersArray)) {
            try {
                $monthStart = now()->startOfMonth()->toISOString();
                $monthEnd = now()->endOfMonth()->toISOString();
                $colourArray = ['#6CC24A', '#B2D33C', '#E3B031', '#F15025', '#8C8C8C'];

                $topControllers = SessionLog::selectRaw('cid, sum(duration) as duration')
                    ->whereBetween('session_start', [$monthStart, $monthEnd])
                    ->groupBy('cid')
                    ->orderByDesc('duration')
                    ->take(5)
                    ->get();

                foreach ($topControllers as $index => $top) {
                    $topControllersArray[] = [
                        'id' => $index,
                        'cid' => $top->cid ?? 'N/A',
                        'time' => function_exists('decimal_to_hm') ? decimal_to_hm($top->duration ?? 0) : 0,
                        'colour' => $colourArray[$index] ?? '#000000',
                    ];
                }

                Cache::put('home.topControllers', $topControllersArray, 900);
            } catch (Exception $e) {
                \Log::error('Failed to fetch top controllers: '.$e->getMessage());
            }
        }

        // Banner update (only if needed)
        try {
            $now = Carbon::now('UTC');

            $ongoingEvent = $nextEvents->first(function ($event) use ($now) {
                return $now->between(
                    Carbon::parse($event->start_timestamp),
                    Carbon::parse($event->end_timestamp)
                );
            });

            $isEventBanner = str_contains($banner->banner, 'Happening Now!');

            $isCustomBanner = ! empty($banner->banner) && ! $isEventBanner;

            if ($isCustomBanner) {
            } elseif ($ongoingEvent) {
                $banner->banner = "🎉 Happening Now! {$ongoingEvent->name}! 🎉";
                $banner->bannerLink = url('/events/'.$ongoingEvent->slug);
                $banner->bannerMode = 'success';

                DB::table('core_info')->update([
                    'banner' => $banner->banner,
                    'bannerLink' => $banner->bannerLink,
                    'bannerMode' => $banner->bannerMode,
                    'updated_at' => now(),
                ]);
            } elseif ($isEventBanner) {
                DB::table('core_info')->update([
                    'banner' => '',
                    'bannerLink' => '',
                    'bannerMode' => '',
                    'updated_at' => now(),
                ]);

                $banner->banner = '';
                $banner->bannerLink = '';
                $banner->bannerMode = '';
            }
        } catch (Exception $e) {
            \Log::error('Failed to update banner: '.$e->getMessage());
        }

        // Placeholder data — shown when DB/cache is empty (testbed / fresh deploy)
        if (empty($finalPositions)) {
            $finalPositions = [
                (object)['callsign' => 'CZVR_CTR',  'cid' => '1301755', 'name' => 'Alex Thompson'],
                (object)['callsign' => 'CYVR_APP',  'cid' => '1456234', 'name' => 'Sarah Kim'],
                (object)['callsign' => 'CYVR_TWR',  'cid' => '1567890', 'name' => 'Mike Davies'],
            ];
        }

        if (empty($weather)) {
            $weather = [
                (object)['icao' => 'CYVR', 'flight_category' => 'VFR',  'raw_text' => 'CYVR 101900Z 27012KT 15SM FEW035 BKN250 13/06 A2991', 'observed' => now()->toISOString()],
                (object)['icao' => 'CYXX', 'flight_category' => 'MVFR', 'raw_text' => 'CYXX 101900Z 26008KT 5SM BR OVC010 09/07 A2989',          'observed' => now()->toISOString()],
                (object)['icao' => 'CYPK', 'flight_category' => 'VFR',  'raw_text' => 'CYPK 101900Z 27010KT 15SM SCT040 12/05 A2992',             'observed' => now()->toISOString()],
                (object)['icao' => 'CYYJ', 'flight_category' => 'IFR',  'raw_text' => 'CYYJ 101900Z 25015G25KT 2SM -RA OVC005 08/07 A2985',       'observed' => now()->toISOString()],
            ];
        }

        if ($nextEvents->isEmpty()) {
            $nextEvents = collect([
                (object)['name' => 'Cross the Pond Westbound',   'start_timestamp' => now()->addDays(3)->format('Y-m-d H:i:s'),  'end_timestamp' => now()->addDays(3)->addHours(4)->format('Y-m-d H:i:s'),  'slug' => 'cross-the-pond'],
                (object)['name' => 'FNO: YVR Friday Night Ops',  'start_timestamp' => now()->addDays(7)->format('Y-m-d H:i:s'),  'end_timestamp' => now()->addDays(7)->addHours(3)->format('Y-m-d H:i:s'),  'slug' => 'fno-yvr'],
                (object)['name' => 'Snowstorm Saturday',         'start_timestamp' => now()->addDays(14)->format('Y-m-d H:i:s'), 'end_timestamp' => now()->addDays(14)->addHours(4)->format('Y-m-d H:i:s'), 'slug' => 'snowstorm-saturday'],
            ]);
        }

        if ($news->isEmpty()) {
            $news = collect([
                (object)['title' => 'Vancouver FIR Modernized Website Launches', 'slug' => 'modernized-website',  '_date' => 'Jun 10, 2026'],
                (object)['title' => 'New ATC Booking System Now Live',           'slug' => 'new-booking-system',   '_date' => 'Jun 5, 2026'],
                (object)['title' => 'Cross the Pond 2026 Dates Announced',       'slug' => 'cross-the-pond-2026',  '_date' => 'May 28, 2026'],
            ]);
        }

        if (empty($topControllersArray)) {
            $topControllersArray = [
                ['cid' => '1301755', 'name' => 'Alex Thompson', 'time' => '42:15', 'minutes' => 2535, 'colour' => '#6CC24A'],
                ['cid' => '1456234', 'name' => 'Sarah Kim',     'time' => '38:30', 'minutes' => 2310, 'colour' => '#B2D33C'],
                ['cid' => '1567890', 'name' => 'Mike Davies',   'time' => '29:45', 'minutes' => 1785, 'colour' => '#E3B031'],
                ['cid' => '1678901', 'name' => 'Jordan Lee',    'time' => '21:00', 'minutes' => 1260, 'colour' => '#F15025'],
            ];
        }

        return view('index', compact('finalPositions', 'news', 'nextEvents', 'topControllersArray', 'weather', 'background', 'banner'));
    }
}
