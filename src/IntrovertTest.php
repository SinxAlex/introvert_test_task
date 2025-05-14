<?php
namespace IntrovertTest;

use Introvert\ApiClient;
use Introvert\Configuration;
class IntrovertTest
{

    /**
     * const HOST_URI адрес хоста
     */
    const   HOST_URI = 'https://api.s1.yadrocrm.ru/';

    /**
     * const STATUS_CLOSED код статуса
     * по документации просит массив
     * ссылка на документацию https://bitbucket.org/mahatmaguru/intr-sdk-test/src/master/
     */
    const   STATUS_CLOSED = [142];

    /**
     * const COUNT_PACKAGE размер пакета
     */
    const   COUNT_PACKAGE = 50;

    /**
     * @da mixed|null
     * param date_from -дата для сортировки
     */
    public $date_from;

    /**
     * @da mixed|null
     * param date_to -дата для сортировки
     */
    public $date_to;

    /**
     * @var ApiClient
     * клиент API
     */
    private $api;

    /**
     * @var array
     * массив клиентов API
     */
    private $apiClients = [];


    /**
     * @param $date_from
     * @param $date_to
     *  проверяю параметры date_from и date_to
     *  если оба пустые или null по умолчанию фильтрация без дат, т.е выборка всех сделок с кодом 142
     *  если указан только один из параметров для даты то ошибка, остановка скрипта
     */
    public function __construct($date_from = null, $date_to = null)
    {
        if ($date_from !== null && $date_to == null || $date_from == null && $date_to !== null) {
            throw new InvalidArgumentException('Ошибка в параметрах даты фильтраций!');
        }

        $this->date_to = $date_to;
        $this->date_from = $date_from;

        /**
         *  получаю список клиентов и прохожу по массиву
         */
        foreach ($this->getClients() as $client) {
        \Introvert\Configuration::getDefaultConfiguration()->setApiKey('key', $client['api'])->setHost(self::HOST_URI);
            $this->api = new \Introvert\ApiClient();
            try {
                /**
                 * если соединение установлено то добавляем в массив $this->apiClients со статусом true
                 * в противном случае false, это удобно так как в случае, когда много клиентов удобно отсортировать
                 * всех у кого нет соединенис API
                 */
                $this->apiClients[$client['name']] = [
                    "status" => "true",
                    "error_msg" => null,
                    "result" => $this->getSum()
                ];
            } catch (\Exception $e) {

                $this->apiClients[$client['name']] = [
                    "status" => "false",
                    "error_msg" => $e->getMessage(),
                    "result" => null
                ];

                error_log("Ошибка: " . $e->getMessage());
            }
        }
    }

    /**
     * @return true|void
     * дополнительная проверка на пустые значения date_to, date_from
     */
    public function isNullDate()
    {
        if (empty($this->date_from) && empty($this->date_to)) {
            return true;
        }
    }

    /**
     * @return array
     * функция для получения суммы, в зависимости от сущестования
     * фильтров запускает функцию для  сортировки
     */
    public function getSum()
    {
        if ($this->isNullDate()) {
            return $this->getSumFilterLeads();
        }
        return $this->getSumFilterLeads([$this, 'filterDate']);
    }


    /**
     * @param $filter
     * @return array
     *
     * функция для подсчета пакетно
     * если есть параметры то запускает через
     * call_user_funk подсчет через сортировку по датам
     *
     */
    public function getSumFilterLeads($filter = null)
    {
        $hasMore = true;
        $offset = 0;
        $sum = 0;
        $countLeads = 0;
        $SUM_ALL = 0;

        while ($hasMore) {
            $res = $this->getLeads(self::COUNT_PACKAGE, $offset);
            if (empty($res)) {
                $hasMore = false;
            }

            $SUM = 0;
            foreach ($res as $lead) {
                $SUM += $lead['price'];
                if ($filter === null || call_user_func($filter, $lead)) {
                    $sum += $lead['price'];
                    $countLeads++;
                }
            }
            $offset += count($res);
            $SUM_ALL += $SUM;
        }

        return [
            'sum' => $sum,
            'count_leads' => $countLeads,
            'ALL_SUM' => $SUM_ALL
        ];
    }

    /**
     * @param $count
     * @param $offset
     * @return mixed
     * @throws \Introvert\ApiException
     *
     * функция для запроса сделок
     * ссылка на документацию https://bitbucket.org/mahatmaguru/intr-sdk-test/src/master/
     */
    private function getLeads($count, $offset)
    {
        $res = $this->api->lead->getAll([], self::STATUS_CLOSED, [], '', $count, $offset);
        return $res['result'];
    }

    /**
     * @param $res
     * @return bool
     *
     * возвращает true если сделка между датами сортировки
     *
     */
    private function filterDate($res)
    {
        return $res['date_close'] >= strtotime($this->date_from)
            && $res['date_close'] <= strtotime($this->date_to);
    }

    /**
     * @return array[]
     *
     * функция возращает список клиентов виде массивва
     * в случае необходимости можно переписать
     * для получения списка по REST API
     */
    protected function getClients()
    {
        return [
            [
                "id" => 1,
                "name" => "intrdev",
                "api" => "23bc075b710da43f0ffb50ff9e889aed",
            ],
            [
                "id" => 2,
                "name" => "artedegrass0",
                "api" => "",
            ],
        ];

    }


    /**
     * @return array
     * Функция для возращение результата выборки для
     * dataProvider фреймворков например: Yii2;
     */

    public function returnProviderData()
    {
        return $this->apiClients;
    }

    public function renderHtml()
    {
        $thead = '<thead>
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Status</th>
                        <th>Count Leads</th>
                        <th>Sum</th>
                        <th>All Sum</th>
                    </tr>
                </thead>';

        $tbody = '<tbody>';
        $counter = 1;


        foreach ($this->apiClients as $clientName => $clientData) {
            $status = isset($clientData['status']) ? $clientData['status'] : '';
            $countLeads = ($status === 'true' && isset($clientData['result']['count_leads']))
                ? $clientData['result']['count_leads']
                : 0;
            $sum = ($status === 'true' && isset($clientData['result']['sum']))
                ? $clientData['result']['sum']
                : 0;
            $all_sum = ($status === 'true' && isset($clientData['result']['ALL_SUM']))
                ? $clientData['result']['ALL_SUM']
                : 0;


            $tbody .= '
                <tr>
                    <td>' . $counter . '</td>
                    <td>' . $clientName . '</td>
                    <td>' . $status . '</td>
                    <td>' . $countLeads . '</td>
                    <td>' . $sum . '</td>
                     <td>' . $all_sum . '</td>
                </tr>';

            $counter++;
        }
        $date_to = date("Y-m-d", strtotime($this->date_to));
        $date_from = date("Y-m-d", strtotime($this->date_from));
        if ($this->date_from == null && $this->date_to == null) {
            $date_from = "null";
            $date_to = "null";
        }


        $tbody .= '</tbody>';
        $table = "<table class='table table-striped'>" . $thead . $tbody . "</table>";
        $html = '<html>
         <head>
             <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
             <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
         </head>
         <body>
         <div class="container">
         <h1>Сделки:  date_from:' . $date_from . ' && date_to:' . $date_to . '</h1>
           <hr />
         ' . $table . '</div></body>
         </html>';
        echo $html;

        return $html;
    }

}
