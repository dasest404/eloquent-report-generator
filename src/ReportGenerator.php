<?php

namespace LangleyFoxall\EloquentReportGenerator;

use DivineOmega\uxdm\Objects\Migrator;
use DivineOmega\uxdm\Objects\Sources\EloquentSource;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use LangleyFoxall\EloquentReportGenerator\Exceptions\ReportGenerationException;
use LangleyFoxall\EloquentReportGenerator\Interfaces\ReportFormatInterface;

/**
 * Class ReportGenerator
 * @package LangleyFoxall\EloquentReportGenerator
 */
class ReportGenerator
{
    /**
     * @var Model
     */
    private $model;
    /**
     * @var ReportFormatInterface
     */
    private $format;
    /**
     * @var callable
     */
    private $queryCallback;
    /**
     * @var callable
     */
    private $dataRowManipulator;
    /**
     * @var array
     */
    private $fields;
    /**
     * @var array
     */
    private $fieldMap;

    /**
     * ReportGenerator constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param ReportFormatInterface $format
     * @return $this
     */
    public function format(ReportFormatInterface $format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @param callable $queryCallback
     * @return $this
     */
    public function query(callable $queryCallback)
    {
        $this->queryCallback = $queryCallback;
        return $this;
    }

    /**
     * @param callable dataRowManipulator
     * @return $this
     */
    public function dataRowManipulator(callable $dataRowManipulator)
    {
        $this->dataRowManipulator = $dataRowManipulator;
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $fieldMap
     * @return $this
     */
    public function fieldMap(array $fieldMap)
    {
        $this->fieldMap = $fieldMap;
        return $this;
    }


    /**
     * Save report (triggers report generation).
     *
     * @param $filename
     * @return ReportGenerator
     * @throws ReportGenerationException
     */
    public function save($filename)
    {
        $this->generate($filename);
        return $this;
    }

    /**
     * Perform report generation using migrator class.
     *
     * @param $filename
     * @throws ReportGenerationException
     */
    private function generate($filename)
    {
        try {

            $migrator = (new Migrator())
                ->setSource($this->getSource())
                ->setDestination($this->format->getDestination($filename));

            if ($this->fields) {
                $migrator->setFieldsToMigrate($this->fields);
            };

            if ($this->fieldMap) {
                $migrator->setFieldMap($this->fieldMap);
            }

            if ($this->dataRowManipulator) {
                $migrator->setDataRowManipulator($this->dataRowManipulator);
            }

            $migrator->migrate();

        } catch (Exception $e) {
            throw new ReportGenerationException('Error generating report.', 0, $e);
        }
    }

    /**
     * Create and return Eloquent source object, with model and custom query builder.
     *
     * @return EloquentSource
     */
    private function getSource()
    {
        return new EloquentSource(get_class($this->model), $this->queryCallback);
    }
}