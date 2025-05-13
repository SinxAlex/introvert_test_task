<?php
require_once(__DIR__ . '/vendor/autoload.php');
use Introvert\ApiClient;


class SumLeads {
    const   HOST_URI='https://api.s1.yadrocrm.ru/';
    const   STATUS_CLOSED=[142];

    const   COUNT_PACKAGE=50;
    public  $date_from;
    public  $date_to;
    private $api;
    private $apiClients=[];


    public function __construct($date_from=null,$date_to=null)
    {
        if($date_from!==null && $date_to==null || $date_from==null && $date_to!==null)
        {
            throw new InvalidArgumentException('Ошибка в параметрах даты фильтраций!');
            exit();
        }

        $this->date_to  =$date_to;
        $this->date_from=$date_from;

      foreach ($this->getClients() as $client)
        {
            Introvert\Configuration::getDefaultConfiguration()->setApiKey('key',$client['api'])->setHost(self::HOST_URI);

            try {
                $this->api = new Introvert\ApiClient();
                $this->apiClients[$client['name']]=[
                    "status"    =>"OK",
                    "error_msg" =>null,
                    "result" => $this->getSum()
                    ];
            } catch (Throwable $e) {

                $this->apiClients[$client['name']]= [
                        "status"    =>"false",
                        "error_msg" =>$e->getMessage(),
                        "result" => null
                   ];

                error_log("Ошибка: " . $e->getMessage());
            }
        }
    }

    public function isNullDate()
    {
        if(empty($this->date_from) && empty($this->date_to)) {
            return true;
        }
    }
    public function getSum()
    {
        if($this->isNullDate())
        {
            return $this->getSumFilterLeads();
        }
        return $this->getSumFilterLeads([$this,'filterDate']);
    }

    public function getSumFilterLeads($filter = null)
    {
        $hasMore  = true;
        $offset = 0;
        $sum = 0;
        $countLeads = 0;

        while ($hasMore) {
            $res = $this->getLeads(self::COUNT_PACKAGE, $offset);
            if (empty($res)) {
                $hasMore = false;
            }

            foreach ($res as $lead) {
                if ($filter === null || call_user_func($filter, $lead)) {
                    $sum += $lead['price'];
                    $countLeads++;
                }
            }
            $offset += count($res);
        }

        return [
            'sum' => $sum,
            'count_leads' => $countLeads
        ];
    }
    private function getLeads($count,$offset)
    {
        $res=$this->api->lead->getAll([], self::STATUS_CLOSED, [], '', $count, $offset);
        return $res['result'];
    }

    private function filterDate($res)
    {
        return $res['date_close'] >= strtotime($this->date_from)
            && $res['date_close'] <= strtotime($this->date_to);
    }

    protected  function getClients()
    {
        return [
            [
                "id" => 1,
                "name" =>"intrdev",
                "api" => "23bc075b710da43f0ffb50ff9e889aed",
            ],
            [
                "id" => 2,
                "name" => "artedegrass0",
                "api" => "",
            ],
        ];

    }

    public function renderHtml()
    {
        $thead = <<<HTML
        <thead>
            <tr>
                <th>#</th>
                <th>User Name</th>
                <th>Status</th>
                <th>Count Leads</th>
                <th>Sum</th>
            </tr>
        </thead>
        HTML;

        $tbody = '<tbody>';
        $counter = 1;

        foreach ($this->apiClients as $clientName => $clientData) {
            $status = $clientData['status'];
            $countLeads = $status === 'OK' ? $clientData['result']['count_leads'] : 'null';
            $sum = $status === 'OK' ? $clientData['result']['sum'] : 'null';

            $tbody .= <<<HTML
    <tr>
        <td>$counter</td>
        <td>$clientName</td>
        <td>$status</td>
        <td>$countLeads</td>
        <td>$sum</td>
    </tr>
HTML;

         $counter++;
        }

        $tbody .= '</tbody>';

        return $thead . $tbody;
    }

}

$test=new SumLeads('2025-01-01','2025-05-30');
$test->renderHtml();