public function index()
{
    $categories = Cache::remember('categories_list', 60, function () {
        return Category::all();
    });

    return response()->json($categories, 200);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|unique:categories,name',
        'description' => 'nullable|string',
    ]);

    $category = Category::create($validated);

    Cache::forget('categories_list');

    return response()->json($category, 201);
}
