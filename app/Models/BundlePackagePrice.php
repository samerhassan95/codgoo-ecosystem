<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundlePackagePrice extends Model
{
    protected $fillable = [
        'bundle_package_id', 'name', 'amount', 'currency', 'duration_days'
    ];

    public function bundlePackage()
    {
        return $this->belongsTo(BundlePackage::class);
    }
}
