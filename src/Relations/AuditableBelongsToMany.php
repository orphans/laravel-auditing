<?php

namespace OwenIt\Auditing\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditableBelongsToMany extends BelongsToMany
{
    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $attributes = $this->addTimestampsToAttachment($attributes, true);
        }
        $updated = $this->newPivotStatementForId($id)->update($attributes);

        //TODO need to load the original attributes for old_pivot_data param.
        $this->parent->fireAuditableModelEvent('pivot_updated', $this->getTable(), $this->getAuditPivotData($id, []), $this->getAuditPivotData($id, $attributes));
        if ($touch) {
            $this->touchIfTouching();
        }
        return $updated;
    }
    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        // Here we will insert the attachment records into the pivot table. Once we have
        // inserted the records, we will touch the relationships if necessary and the
        // function will return. We can parse the IDs before inserting the records.
        $this->newPivotStatement()->insert($this->formatAttachRecords(
            $this->parseIds($id), $attributes
        ));

        $this->parent->fireAuditableModelEvent('pivot_attached', $this->getTable(), [], $this->getAuditPivotData($id, $attributes));
        if ($touch) {
            $this->touchIfTouching();
        }
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        $query = $this->newPivotQuery();
        // If associated IDs were passed to the method we will only delete those
        // associations, otherwise all of the association ties will be broken.
        // We'll return the numbers of affected rows when we do the deletes.
        if (! is_null($ids)) {
            $ids = $this->parseIds($ids);
            if (empty($ids)) {
                return 0;
            }
            $query->whereIn($this->relatedPivotKey, (array) $ids);
        }
        // Once we have all of the conditions set on the statement, we are ready
        // to run the delete on the pivot table. Then, if the touch parameter
        // is true, we will go ahead and touch all related models to sync.
        $results = $query->delete();
        foreach($ids as $id) {
            //TODO need to load the original attributes for old_pivot_data param.
            $this->parent->fireAuditableModelEvent('pivot_detached', $this->getTable(), $this->getAuditPivotData($id, []), []);
        }
        if ($touch) {
            $this->touchIfTouching();
        }
        return $results;
    }

    /**
     * Get the pivot data into auditable format.
     *
     * @param Integer $id
     * @param Array $attributes
     * @return void
     */
    protected function getAuditPivotData($id, $attributes) {
        $data = [
            $this->relatedPivotKey => $id
        ];

        return array_merge($data, $attributes);
    }
}
