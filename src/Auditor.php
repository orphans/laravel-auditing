<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Contracts\AuditDriver;
use OwenIt\Auditing\Contracts\Auditor as AuditorContract;
use OwenIt\Auditing\Drivers\Database;
use OwenIt\Auditing\Events\Audited;
use OwenIt\Auditing\Events\Auditing;
use RuntimeException;

class Auditor extends Manager implements AuditorContract
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['audit.default'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $exception) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function auditDriver(AuditableContract $model)
    {
        $driver = $this->driver($model->getAuditDriver());

        if (!$driver instanceof AuditDriver) {
            throw new RuntimeException('The driver must implement the AuditDriver contract');
        }

        return $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AuditableContract $model, $table_name = null, $old_pivot_data = null, $new_pivot_data = null)
    {
        $is_pivot = !is_null($old_pivot_data) || !is_null($new_pivot_data);

        if (!$model->readyForAuditing()) {
            return;
        }

        $driver = $this->auditDriver($model);

        if($is_pivot) {
            if (!$this->fireAuditingPivotEvent($model, $driver, $table_name)) {
                return;
            }
        } else {
            if (!$this->fireAuditingEvent($model, $driver)) {
                return;
            }
        }

        if(is_null($table_name)) {
            $table_name = $model->getTable();
        }

        if($is_pivot) {
            if ($audit = $driver->auditPivot($model, $table_name, $old_pivot_data, $new_pivot_data)) {
                $driver->prune($model);
            }
        } else {
            if ($audit = $driver->audit($model,$table_name)) {
                $driver->prune($model);
            }
        }

        if($is_pivot) {
            $this->app->make('events')->fire(
                new AuditedPivot($model, $driver, $audit, $table_name)
            );
        } else {
            $this->app->make('events')->fire(
                new Audited($model, $driver, $audit)
            );
        }
    }

    /**
     * Create an instance of the Database audit driver.
     *
     * @return \OwenIt\Auditing\Drivers\Database
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(Database::class);
    }

    /**
     * Fire the Auditing event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     *
     * @return bool
     */
    protected function fireAuditingEvent(AuditableContract $model, AuditDriver $driver)
    {
        return $this->app->make('events')->until(
            new Auditing($model, $driver)
        ) !== false;
    }

    /**
     * Fire the Auditing pivot event.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     *
     * @return bool
     */
    protected function fireAuditingPivotEvent(AuditableContract $model, AuditDriver $driver, $table_name)
    {
        return $this->app->make('events')->until(
            new AuditingPivot($model, $driver, $table_name)
        ) !== false;
    }
}
