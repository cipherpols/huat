<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $companyModel = new \App\Company();
        $timeFlame = $request->input('time-flame', '7');
        $dataFilter = $request->input('data-filter');
        $excludedCompany = $request->input('excluded-company', []);

        $companyList = $companyModel->getCompanyList($companyModel->getQueryFromFilter($timeFlame, $dataFilter, []));

        $timeList = [
            '7' => '7 days',
            '30' => '30 days',
            '90' => '90 days',
        ];

        $dataFilterList = [
            '' => 'All',
            'mna' => 'M&amp;A',
            'hot_mna' => 'Hot M&amp;A',
            'trading_halt' => 'Trading Halt',
        ];

        return view('home.index', [
            'companyList' => $this->generateOptionsHtml($companyList, $excludedCompany, false),
            'timeFlame' => $this->generateOptionsHtml($timeList, $timeFlame),
            'dataFilter' => $this->generateOptionsHtml($dataFilterList, $dataFilter),
            'resultList' => $companyModel->search($timeFlame, $dataFilter, $excludedCompany)->toArray(),
        ]);
    }

    /**
     * @param $list
     * @param $selected
     * @param bool $useIndexAsValue
     * @return string
     */
    private function generateOptionsHtml($list, $selected, $useIndexAsValue = true)
    {
        $html = '';
        if ($selected !== null) {
            if (!is_array($selected)) {
                $selected = [$selected];
            }
            $hasSelected = true;
        } else {
            $selected = [];
            $hasSelected = false;
        }

        foreach ($list as $value => $item) {
            $value = $useIndexAsValue ? $value : $item;
            $selectedString = $hasSelected && in_array($value, $selected) ? ' selected="selected"' : '';
            $html .= sprintf('<option value="%s"%s>%s</option>', $value, $selectedString, $item);
        }

        return $html;
    }
}