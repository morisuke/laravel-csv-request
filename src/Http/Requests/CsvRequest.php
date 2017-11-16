<?php

namespace Morisuke\CsvRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

/**
 * CsvRequest
 *
 * @uses FormRequest
 * @abstract
 * @package
 * @version 1.0
 * @copyright itto.inc
 * @author morisuke <morisuke_tec@outlook.com>
 * @license PHP Version 7.1
 */
abstract class CsvRequest extends FormRequest
{
    /**
     * csvRules
     *
     * @abstract
     * @access protected
     * @return array
     */
    abstract protected function csvRules();

    /**
     * columnNameList
     *
     * @var array|null
     * @access protected
     */
    protected $columnNameList = null;

    /**
     * columnValidationMode
     *
     * @var bool
     * @access protected
     */
    protected $columnValidationMode = false;

    /**
     * validationData
     *
     * @var array
     * @access protected
     */
    protected $validationData = [];

    /**
     * errorColumnNumber
     *
     * @var integer
     * @access protected
     */
    protected $errorColumnNumber = 0;

    /**
     * rules
     *
     * @access public
     * @return mixed
     */
    public function rules()
    {
        return $this->columnValidationMode
            ? $this->csvRules()
            : ['csv' => 'required|mimes:csv,txt'];
    }

    /**
     * formatErrors
     *
     * @param Validator $validator
     * @access protected
     * @return mixed
     */
    protected function formatErrors(Validator $validator)
    {
        return array_merge([
            'csv_column_number' => $this->getCsvColumnNumberErrorMessage(),
        ], $validator->getMessageBag()->toArray());
    }

    /**
     * getCsvColumnNumberErrorMessage
     *
     * @access protected
     * @return mixed
     */
    protected function getCsvColumnNumberErrorMessage()
    {
        return $this->columnValidationMode
            ? "{$this->errorColumnNumber}行目のデータに問題があります"
            : 'CSVアップロード処理でエラーが発生しました';
    }

    /**
     * getColumnNameList
     *
     * @access public
     * @return mixed
     */
    public function getColumnNameList()
    {
        return array_keys($this->csvRules());
    }

    /**
     * setValidationData
     *
     * @param array $validation_data
     * @access protected
     * @return mixed
     */
    protected function setValidationData(array $validation_data)
    {
        $this->validationData = $validation_data;
        return $this;
    }

    /**
     * validationData
     *
     * @access public
     * @return mixed
     */
    public function validationData()
    {
        return $this->columnValidationMode
            ? $this->validationData
            : $this->all();
    }

    /**
     * getCsvIterator
     *
     * @param bool $skip_header
     * @access public
     * @return mixed
     */
    public function getCsvIterator(bool $skip_header = true)
    {
        // 行バリデーションモードに移行
        $this->columnValidationMode = true;

        // CSVを展開
        $csv_obj = $this->csv->openFile();
        $csv_obj->setFlags($csv_obj::READ_CSV);

        // CSVを回す
        foreach ($csv_obj as $i => $column)
        {
            // フォーマットとカラム数があっていない場合
            if (count($this->getColumnNameList()) !== count($column))
            {
                continue;
            }

            // ヘッダスキップがONになっており、かつ1行目の場合
            if ($skip_header && $i === 0)
            {
                continue;
            }

            // 各行のデータをUTF-8変換
            $column = array_map(function($v) {
                return mb_convert_encoding($v, 'UTF-8', 'ASCII,UTF-8,SJIS-win');
            }, $column);

            // バリデーション対象行数
            $this->errorColumnNumber = $i;

            // バリデーションを実行
            $validation_data = array_combine($this->getColumnNameList(), $column);
            $this->setValidationData($validation_data)->validate($this->csvRules());

            // 1行ずつ返却
            yield $i => collect($validation_data);
        }

        // 行バリデーションモード終了
        $this->columnValidationMode = false;
    }
}
