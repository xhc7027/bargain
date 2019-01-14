<?php
namespace app\services\data;

use app\models\Bargain;
use app\models\BargainContribution;
use app\models\Event;
use app\commons\StringUtil;

/**
 * 数据统计类，对数据进行做处理
 * 实现数据统计对外接口
 */
class DataFacadeApi implements DataFacade
{
    /**
     * 获取excel的数据
     * @param $activityStatistic -- 传入的是一个对象
     * @return \PHPExcel
     */
    public function getActivityExcel($activityStatistic)
    {
        //过滤查询获取数据
        $listInfo = Bargain::find()->select([
            'eventId', 'type', 'price', 'isLowestPrice', 'startTime', 'resourceStatus',
            'contact.name', 'contact.phone', 'contact.address', 'bargainPrice', 'resourceExplain'
        ])->asArray()->where(['eventId' => $activityStatistic->eventId]);
        //因为如果endTime和startTime为空时，转为int的时候会是0，过滤就会不起作用，因为过滤只是过滤空值
        $listInfo->andFilterWhere(['=', 'resourceStatus', $activityStatistic->resourceStatus]);
        if (!empty($activityStatistic->startTime)) {
            $listInfo->andFilterWhere(['>=', 'startTime', intval($activityStatistic->startTime)]);
        }
        if (!empty($activityStatistic->endTime)) {
            $listInfo->andFilterWhere(['<=', 'startTime', intval($activityStatistic->endTime)]);
        }
        $listInfo->andFilterWhere([
            'or',
            ['like', 'contact.name', $activityStatistic->searchByNameOrPhone],
            ['like', 'contact.phone', $activityStatistic->searchByNameOrPhone]
        ]);
        $data = $listInfo->orderBy('startTime desc')->asArray()->all();

        if ($data) {
            $data = $this->createActivityExcelData($data);
        }
        return $this->createExcel($data, $activityStatistic->isMall);
    }

    /**
     * 组装excel的数据
     * @param $data
     * @return mixed
     */
    private function createActivityExcelData($data)
    {
        $event = Event::getEventInfoOne(['_id' => $data[0]['eventId']], ['resources.name']);
        $bargainIds = $bargainCon = [];
        //先组装基本的数据
        foreach ($data as $key => $val) {
            $bargainIds[] = $data[$key]['_id']->__toString();
            $data[$key]['goodsName'] = $event['resources']['name'];
            $data[$key]['startTime'] = date("Y-m-d H:i:s", $data[$key]['startTime']);
            if (isset($data[$key]['contact'])) {
                $data[$key]['name'] = StringUtil::dealEmoji($data[$key]['contact']['name']);
                $data[$key]['phone'] = $data[$key]['contact']['phone'];
                if (isset($data[$key]['contact']['address'])) {
                    $data[$key]['address'] = $data[$key]['contact']['address'];
                } else {
                    $data[$key]['address'] = '';
                }
            } else {
                $data[$key]['name'] = '';
                $data[$key]['phone'] = '';
                $data[$key]['address'] = '';
            }
            $data[$key]['bargainPrice'] = $data[$key]['price'] - $data[$key]['bargainPrice'];
        }

        //查出该活动的所有贡献者
        $bargainContributions = BargainContribution::getBargainConAsArrayAll(
            ['bargainId' => ['$in' => $bargainIds]], ['_id' => null, 'bargainId']
        );
        foreach ($bargainContributions as $k => $v) {
            $bargainCon[] = $bargainContributions[$k]['bargainId'];
        }
        //统计每个bargainId出现的次数
        $appearStatistics = array_count_values($bargainCon);

        //遍历组装的数据进行
        foreach ($data as $key => $val) {
            $bargainId = $data[$key]['_id']->__toString();
            isset($appearStatistics[$bargainId])
                ? $data[$key]['helpBargainNum'] = $appearStatistics[$bargainId]
                : $data[$key]['helpBargainNum'] = 0;
            unset($data[$key]['_id'], $data[$key]['contact']);
        }

        return $data;
    }

    /**
     * 创建excel表结构
     * @param $data
     * @param $isMall
     * isMall 0-微商城呢商品  1-非微商城呢商品
     * @return \PHPExcel
     */
    private function createExcel($data, $isMall)
    {
        $file = "商家用户" . date("YmdHis");
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $obj = $objPHPExcel->setActiveSheetIndex(0);
        //合并第一行
        $obj->setCellValue('A1', '商家活动统计列表(' . date('YmdHis') . ')');
        $styleArray1 = array(
            'font' => array(
                'bold' => true,
                'color' => array(
                    'argb' => 'FF000000',
                ),
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray1);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
        $styleArray2 = array(
            'font' => array(
                'color' => array(
                    'argb' => 'FFF0F4F7',
                ),
            ),
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        $styleArray3 = array(
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wraptext' => true,
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                ),
            ),
        );
        //设置第二行的数据
        $objPHPExcel->getActiveSheet()->setCellValue('A2', '时间')
            ->setCellValue('B2', '姓名')
            ->setCellValue('C2', '手机号')
            ->setCellValue('D2', '地址')
            ->setCellValue('E2', '商品名称')
            ->setCellValue('F2', '帮砍人数')
            ->setCellValue('G2', '当前金额');
        if ($isMall == 1) {
            $objPHPExcel->getActiveSheet()->setCellValue('H2', '兑奖码')
                ->setCellValue('I2', '兑奖状态');
            $objPHPExcel->getActiveSheet()->getStyle('A2:I2')->applyFromArray($styleArray2);
            $objPHPExcel->getActiveSheet()->getStyle('A2:I2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A2:I2')->getFill()->getStartColor()->setARGB('FF7292BE');
            //显示组装的数据
            $num = 3;
            foreach ($data as $k => $v) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $num, $v['startTime'])
                    ->setCellValue('B' . $num, $v['name'])
                    ->setCellValue('C' . $num, $v['phone'])
                    ->setCellValue('D' . $num, $v['address'])
                    ->setCellValue('E' . $num, $v['goodsName'])
                    ->setCellValue('F' . $num, $v['helpBargainNum'])
                    ->setCellValue('G' . $num, $v['bargainPrice'])
                    ->setCellValue('H' . $num, $v['resourceExplain'])
                    ->setCellValue('I' . $num, $v['resourceStatus']);
                $num++;
            }
            //设置居中以及每列的大小
            $objPHPExcel->getActiveSheet()->getStyle('A2:I' . ($num - 1))->applyFromArray($styleArray3);
        } else if ($isMall == 0) {
            $objPHPExcel->getActiveSheet()->setCellValue('H2', '商品状态');
            $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->applyFromArray($styleArray2);
            $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->getFill()->getStartColor()->setARGB('FF7292BE');
            //显示组装的数据
            $num = 3;
            foreach ($data as $k => $v) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $num, $v['startTime'])
                    ->setCellValue('B' . $num, $v['name'])
                    ->setCellValue('C' . $num, $v['phone'])
                    ->setCellValue('D' . $num, $v['address'])
                    ->setCellValue('E' . $num, $v['goodsName'])
                    ->setCellValue('F' . $num, $v['helpBargainNum'])
                    ->setCellValue('G' . $num, $v['bargainPrice'])
                    ->setCellValue('H' . $num, $v['resourceStatus']);
                $num++;
            }
            //设置居中以及每列的大小
            $objPHPExcel->getActiveSheet()->getStyle('A2:H' . ($num - 1))->applyFromArray($styleArray3);
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('25');
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('20');
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('20');
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('35');
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('35');
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('15');
        if ($isMall == 1) {
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('15');
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('10');
        } else if ($isMall == 0) {
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('15');
        }
        $objPHPExcel->setActiveSheetIndex(0);
        //设置活动工作簿的标题
        $objPHPExcel->getActiveSheet()->setTitle($file);
        //设置当前工作簿为第一个工作簿
        $objPHPExcel->setActiveSheetIndex(0);
        return $objPHPExcel;
    }
}