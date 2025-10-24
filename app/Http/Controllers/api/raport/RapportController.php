<?php

namespace App\Http\Controllers\api\raport;

use App\Models\Sale;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class RapportController extends Controller
{
    /**
     * Tendances des ventes
     */
    public function trends(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, quarter, year
        $groupBy = $request->get('group_by', 'day'); // day, week, month
        
        $dates = $this->getPeriodDates($period);
        $sales = Sale::whereBetween('sale_date', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->get();
        
        $trends = $this->groupSalesByPeriod($sales, $groupBy);
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'group_by' => $groupBy,
                'trends' => $trends,
                'summary' => [
                    'total_revenue' => $sales->sum('total'),
                    'total_sales' => $sales->count(),
                    'average_per_period' => count($trends) > 0 ? $sales->sum('total') / count($trends) : 0,
                    'highest_period' => $this->getHighestPeriod($trends),
                    'lowest_period' => $this->getLowestPeriod($trends),
                ],
            ]
        ]);
    }

    /**
     * Prédictions basées sur l'historique
     */
    public function forecasts(Request $request)
    {
        $type = $request->get('type', 'sales'); // sales, revenue, inventory
        $months = $request->get('months', 3); // Nombre de mois à prédire
        
        // Récupérer les données historiques (12 derniers mois)
        $historicalData = $this->getHistoricalData($type, 12);
        
        // Calculer la tendance (régression linéaire simple)
        $forecast = $this->calculateForecast($historicalData, $months);
        
        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'historical_data' => $historicalData,
                'forecast' => $forecast,
                'confidence' => $this->calculateConfidence($historicalData),
            ]
        ]);
    }

    /**
     * Comportement client
     */
    public function customerBehavior(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonths(3));
        $endDate = $request->get('end_date', now());
        
        $sales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->with('customer')
            ->get();
        
        $customers = $sales->groupBy('customer_id');
        
        $behavior = [
            'purchase_frequency' => [
                'one_time' => $customers->filter(fn($s) => $s->count() == 1)->count(),
                'repeat' => $customers->filter(fn($s) => $s->count() > 1)->count(),
                'loyal' => $customers->filter(fn($s) => $s->count() >= 5)->count(),
            ],
            'average_purchase_value' => $sales->count() > 0 ? $sales->sum('total') / $sales->count() : 0,
            'customer_lifetime_value' => $customers->map(fn($s) => $s->sum('total'))->avg(),
            'churn_rate' => $this->calculateChurnRate($startDate, $endDate),
            'top_customers' => $this->getTopCustomers($startDate, $endDate, 10),
            'customer_segments' => $this->segmentCustomers($customers),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $behavior
        ]);
    }

    /**
     * Performance des produits
     */
    public function productPerformance(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth());
        $endDate = $request->get('end_date', now());
        
        $productStats = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->select(
                'products.id',
                'products.name',
                'products.category_id',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                DB::raw('AVG(sale_items.unit_price) as avg_price'),
                DB::raw('COUNT(DISTINCT sales.id) as number_of_sales')
            )
            ->groupBy('products.id', 'products.name', 'products.category_id')
            ->get();
        
        $performance = [
            'best_sellers' => $productStats->sortByDesc('total_sold')->take(10)->values(),
            'highest_revenue' => $productStats->sortByDesc('total_revenue')->take(10)->values(),
            'slow_movers' => $productStats->sortBy('total_sold')->take(10)->values(),
            'by_category' => $productStats->groupBy('category_id')->map(function($group) {
                return [
                    'total_sold' => $group->sum('total_sold'),
                    'total_revenue' => $group->sum('total_revenue'),
                    'product_count' => $group->count(),
                ];
            }),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    // Méthodes privées d'aide
    
    private function groupSalesByPeriod($sales, $groupBy)
    {
        return $sales->groupBy(function($sale) use ($groupBy) {
            switch ($groupBy) {
                case 'day':
                    return $sale->sale_date->format('Y-m-d');
                case 'week':
                    return $sale->sale_date->format('Y-W');
                case 'month':
                    return $sale->sale_date->format('Y-m');
                default:
                    return $sale->sale_date->format('Y-m-d');
            }
        })->map(function($group, $period) {
            return [
                'period' => $period,
                'revenue' => $group->sum('total'),
                'sales_count' => $group->count(),
                'average' => $group->avg('total'),
            ];
        })->values();
    }

    private function getHistoricalData($type, $months)
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->startOfMonth()->format('Y-m-d');
            $endDate = $date->endOfMonth()->format('Y-m-d');
            
            $value = match($type) {
                'sales' => Sale::whereBetween('sale_date', [$startDate, $endDate])->count(),
                'revenue' => Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total'),
                'inventory' => Produit::sum(DB::raw('stock_quantity * purchase_price')),
                default => 0,
            };
            
            $data[] = [
                'period' => $date->format('Y-m'),
                'value' => $value,
            ];
        }
        
        return $data;
    }

    private function calculateForecast($historicalData, $months)
    {
        $n = count($historicalData);
        if ($n < 2) return [];
        
        // Régression linéaire simple
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($historicalData as $i => $data) {
            $x = $i;
            $y = $data['value'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Générer les prédictions
        $forecast = [];
        for ($i = 0; $i < $months; $i++) {
            $x = $n + $i;
            $predictedValue = $slope * $x + $intercept;
            $date = now()->addMonths($i + 1);
            
            $forecast[] = [
                'period' => $date->format('Y-m'),
                'predicted_value' => max(0, round($predictedValue, 2)),
            ];
        }
        
        return $forecast;
    }

    private function calculateConfidence($data)
    {
        if (count($data) < 3) return 'low';
        
        // Calculer la variance pour déterminer la confiance
        $values = array_column($data, 'value');
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        $coefficient = $mean > 0 ? sqrt($variance) / $mean : 0;
        
        if ($coefficient < 0.3) return 'high';
        if ($coefficient < 0.6) return 'medium';
        return 'low';
    }

    private function getTopCustomers($startDate, $endDate, $limit)
    {
        return DB::table('sales')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(sales.id) as purchase_count'),
                DB::raw('SUM(sales.total) as total_spent')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    private function segmentCustomers($customers)
    {
        return [
            'high_value' => $customers->filter(fn($s) => $s->sum('total') > 10000)->count(),
            'medium_value' => $customers->filter(fn($s) => $s->sum('total') >= 5000 && $s->sum('total') <= 10000)->count(),
            'low_value' => $customers->filter(fn($s) => $s->sum('total') < 5000)->count(),
        ];
    }

    private function getHighestPeriod($trends)
    {
        return $trends->sortByDesc('revenue')->first();
    }

    private function getLowestPeriod($trends)
    {
        return $trends->sortBy('revenue')->first();
    }

    private function getPeriodDates($period)
    {
        return match($period) {
            'week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'quarter' => ['start' => now()->startOfQuarter(), 'end' => now()->endOfQuarter()],
            'year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }
}
