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

namespace OwenIt\Auditing\Events;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;

class AuditingPivot
{
    /**
     * The Auditable model.
     *
     * @var \OwenIt\Auditing\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \OwenIt\Auditing\Contracts\AuditDriver
     */
    public $driver;

    public $table_name;

    /**
     * Create a new Auditing event instance.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable   $model
     * @param \OwenIt\Auditing\Contracts\AuditDriver $driver
     */
    public function __construct(Auditable $model, AuditDriver $driver, $table_name)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->table_name = $table_name;
    }
}
