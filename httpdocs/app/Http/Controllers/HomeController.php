<?php

/**
 * This file is part of the Huat package.
 */

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;

/**
 * Class HomeController
 * @package App\Http\Controllers
 * @author Sabo Duy Nguyen <nhduy88@gmail.com>
 * @website http://cipherpols.com/
 */
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
            Company::MNA => 'M&amp;A',
            Company::HOT_MNA => 'Hot M&amp;A',
            Company::TRADING_HALT => 'Trading Halt',
        ];

        return view('home.index', [
            'timezone' => $companyModel->getConfig()['timezone'],
            'companyList' => $this->generateOptionsHtml($companyList, $excludedCompany, false),
            'timeFlame' => $this->generateOptionsHtml($timeList, $timeFlame),
            'dataFilter' => $this->generateOptionsHtml($dataFilterList, $dataFilter),
            'resultList' => $companyModel->search($timeFlame, $dataFilter, $excludedCompany),
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
            $html .= sprintf('<option value="%s" %s>%s</option>', $value, $selectedString, $item);
        }

        return $html;
    }
}
