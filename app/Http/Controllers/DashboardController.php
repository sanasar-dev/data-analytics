<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google_Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $today = Carbon::now()->format('Y-m-d');
//        dd(Carbon::createFromTime('20')->format('H:i:s'));

//        $client = new Google_Client();
//        $client->setDeveloperKey('AIzaSyBkEY1mBK6qzBFvTdg24VOFtFzdS_bdsjY');
//        $analytics = new \Google_Service_Analytics($client);
//        dd($analytics->data_ga->get('4658238091', $startDate, Carbon::now()->format('Y-m-d'), 'city'));

        $formating = [
            'date' => 'formatDate',
            'dayOfWeek' => 'formatDayOfWeek',
            'hour' => 'formatHour',
        ];

        $client = new BetaAnalyticsDataClient();

        $response = $client->runReport([
            'property' => 'properties/' . config('google.google_property_id'),
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate,
                    'end_date' => $today,
                ]),
            ],
            'dimensions' => [
                new Dimension(
                    [
                        'name' => 'browser',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'country',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'date',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'dayOfWeek',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'hour',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'minute',
                    ]
                ),
                new Dimension(
                    [
                        'name' => 'deviceCategory',
                    ]
                ),
            ],
            'metrics' => [new Metric(
                [
                    'name' => 'totalUsers',
                ]
            )
            ]
        ]);

        $reports = [];

        foreach ($response->getRows() as $key => $row) {
            foreach ($row->getDimensionValues() as $value) {
                $reports[$key][] = $value->getValue();
            }

            $reports[$key]['value'] = $row->getMetricValues()[0]->getValue();
        }

        foreach ($reports as &$report) {
            foreach ($response->getDimensionHeaders() as $key => $dimension) {
                $report[$dimension->getName()] = $report[$key];
                unset($report[$key]);

                if (isset($formating[$dimension->getName()])) {
                    $report[$dimension->getName()] = $this->{$formating[$dimension->getName()]}($report[$dimension->getName()]);
                }
            }
        }

//        dd($report);
        return view('dashboard', ['reports' => $reports]);
    }

    /**
     * @param $date
     * @return string
     */
    private function formatDate($date): string
    {
        return Carbon::parse($date)->format('Y-m-d');
    }

    /**
     * @param $day
     * @return mixed
     */
    private function formatDayOfWeek($day): string
    {
        return Carbon::getDays()[$day];
    }

    /**
     * @param $hour
     * @return string
     */
    private function formatHour($hour): string
    {
        return Carbon::createFromTime($hour)->format('H:i:s');
    }
}
