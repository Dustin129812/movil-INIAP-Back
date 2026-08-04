<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Investigacion\Entities\ExpenseType;

class ExpenseTypeController extends Controller
{
    public function index()
    {
        $types = ExpenseType::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        return response()->json($types);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|string|in:53,84',
            'query' => 'required|string|min:2',
        ]);

        $query = $validated['query'];
        $group = $validated['group'];

        $results = ExpenseType::where('is_active', true)
            ->where('group', $group)
            ->where(function ($q) use ($query) {
                $searchTerm = '%' . strtolower($query) . '%';
                $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$searchTerm]);
            })
            ->take(15)
            ->get();

        return response()->json($results);
    }
}
