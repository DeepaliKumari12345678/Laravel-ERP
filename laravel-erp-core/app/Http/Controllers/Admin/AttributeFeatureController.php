<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Feature;
use App\Models\FeatureValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttributeFeatureController extends Controller
{
    public function attributes(Request $request): View
    {
        $query = AttributeGroup::query()->withCount('values')->orderBy('position')->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        return view('admin.catalog.attributes', [
            'groups' => $query->paginate(20)->withQueryString(),
            'tab' => 'attributes',
        ]);
    }

    public function createAttribute(): View
    {
        return view('admin.catalog.attribute-form', [
            'group' => new AttributeGroup(['type' => 'select', 'position' => 0]),
            'mode' => 'create',
        ]);
    }

    public function storeAttribute(Request $request): RedirectResponse
    {
        $group = AttributeGroup::query()->create($this->validatedAttributeGroup($request));

        return redirect()
            ->route('admin.catalog.attributes.show', $group)
            ->with('success', 'Attribute created.');
    }

    public function showAttribute(Request $request, AttributeGroup $attributeGroup): View
    {
        $valuesQuery = AttributeValue::query()
            ->where('attribute_group_id', $attributeGroup->id)
            ->orderBy('position')
            ->orderBy('name');

        if ($request->filled('id')) {
            $valuesQuery->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $valuesQuery->where('name', 'like', '%'.$request->string('name').'%');
        }

        return view('admin.catalog.attribute-show', [
            'group' => $attributeGroup,
            'values' => $valuesQuery->paginate(30)->withQueryString(),
            'tab' => 'attributes',
        ]);
    }

    public function editAttribute(AttributeGroup $attributeGroup): View
    {
        return view('admin.catalog.attribute-form', [
            'group' => $attributeGroup,
            'mode' => 'edit',
        ]);
    }

    public function updateAttribute(Request $request, AttributeGroup $attributeGroup): RedirectResponse
    {
        $attributeGroup->update($this->validatedAttributeGroup($request, $attributeGroup));

        return redirect()
            ->route('admin.catalog.attributes.show', $attributeGroup)
            ->with('success', 'Attribute updated.');
    }

    public function destroyAttribute(AttributeGroup $attributeGroup): RedirectResponse
    {
        $attributeGroup->delete();

        return redirect()
            ->route('admin.catalog.attributes')
            ->with('success', 'Attribute deleted.');
    }

    public function createAttributeValueGlobal(Request $request): View|RedirectResponse
    {
        $groups = AttributeGroup::query()->orderBy('position')->orderBy('name')->get();

        if ($groups->isEmpty()) {
            return redirect()
                ->route('admin.catalog.attributes.create')
                ->with('error', 'Create an attribute first, then add values.');
        }

        $selectedId = $request->integer('attribute_group_id') ?: $groups->first()->id;

        return view('admin.catalog.attribute-value-form', [
            'groups' => $groups,
            'group' => $groups->firstWhere('id', $selectedId) ?? $groups->first(),
            'value' => new AttributeValue([
                'attribute_group_id' => $selectedId,
                'position' => 0,
            ]),
            'mode' => 'create',
            'allowGroupSelect' => true,
        ]);
    }

    public function storeAttributeValueGlobal(Request $request): RedirectResponse
    {
        $group = AttributeGroup::query()->findOrFail($request->integer('attribute_group_id'));
        $value = $group->values()->create($this->validatedAttributeValue($request, $group));

        if ($request->boolean('save_and_add')) {
            return redirect()
                ->route('admin.catalog.attribute-values.create', ['attribute_group_id' => $group->id])
                ->with('success', 'Value “'.$value->name.'” saved. Add another.');
        }

        return redirect()
            ->route('admin.catalog.attributes.show', $group)
            ->with('success', 'Attribute value created.');
    }

    public function createAttributeValue(AttributeGroup $attributeGroup): View
    {
        return view('admin.catalog.attribute-value-form', [
            'groups' => AttributeGroup::query()->orderBy('position')->orderBy('name')->get(),
            'group' => $attributeGroup,
            'value' => new AttributeValue(['attribute_group_id' => $attributeGroup->id, 'position' => 0]),
            'mode' => 'create',
            'allowGroupSelect' => true,
        ]);
    }

    public function storeAttributeValue(Request $request, AttributeGroup $attributeGroup): RedirectResponse
    {
        $groupId = $request->integer('attribute_group_id') ?: $attributeGroup->id;
        $group = AttributeGroup::query()->findOrFail($groupId);
        $value = $group->values()->create($this->validatedAttributeValue($request, $group));

        if ($request->boolean('save_and_add')) {
            return redirect()
                ->route('admin.catalog.attributes.values.create', $group)
                ->with('success', 'Value “'.$value->name.'” saved. Add another.');
        }

        return redirect()
            ->route('admin.catalog.attributes.show', $group)
            ->with('success', 'Attribute value created.');
    }

    public function editAttributeValue(AttributeGroup $attributeGroup, AttributeValue $attributeValue): View
    {
        abort_unless($attributeValue->attribute_group_id === $attributeGroup->id, 404);

        return view('admin.catalog.attribute-value-form', [
            'groups' => AttributeGroup::query()->orderBy('position')->orderBy('name')->get(),
            'group' => $attributeGroup,
            'value' => $attributeValue,
            'mode' => 'edit',
            'allowGroupSelect' => true,
        ]);
    }

    public function updateAttributeValue(Request $request, AttributeGroup $attributeGroup, AttributeValue $attributeValue): RedirectResponse
    {
        abort_unless($attributeValue->attribute_group_id === $attributeGroup->id, 404);

        $groupId = $request->integer('attribute_group_id') ?: $attributeGroup->id;
        $group = AttributeGroup::query()->findOrFail($groupId);
        $data = $this->validatedAttributeValue($request, $group, $attributeValue);
        $data['attribute_group_id'] = $group->id;
        $attributeValue->update($data);

        return redirect()
            ->route('admin.catalog.attributes.show', $group)
            ->with('success', 'Attribute value updated.');
    }

    public function destroyAttributeValue(AttributeGroup $attributeGroup, AttributeValue $attributeValue): RedirectResponse
    {
        abort_unless($attributeValue->attribute_group_id === $attributeGroup->id, 404);
        $attributeValue->delete();

        return back()->with('success', 'Attribute value deleted.');
    }

    public function features(Request $request): View
    {
        $query = Feature::query()->withCount('values')->orderBy('position')->orderBy('name');

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        return view('admin.catalog.features', [
            'features' => $query->paginate(20)->withQueryString(),
            'tab' => 'features',
        ]);
    }

    public function createFeature(): View
    {
        return view('admin.catalog.feature-form', [
            'feature' => new Feature(['position' => 0]),
            'mode' => 'create',
        ]);
    }

    public function storeFeature(Request $request): RedirectResponse
    {
        $feature = Feature::query()->create($this->validatedFeature($request));

        return redirect()
            ->route('admin.catalog.features.show', $feature)
            ->with('success', 'Feature created.');
    }

    public function showFeature(Request $request, Feature $feature): View
    {
        $valuesQuery = FeatureValue::query()
            ->where('feature_id', $feature->id)
            ->orderBy('position')
            ->orderBy('value');

        if ($request->filled('id')) {
            $valuesQuery->where('id', $request->integer('id'));
        }
        if ($request->filled('value')) {
            $valuesQuery->where('value', 'like', '%'.$request->string('value').'%');
        }

        return view('admin.catalog.feature-show', [
            'feature' => $feature,
            'values' => $valuesQuery->paginate(30)->withQueryString(),
            'tab' => 'features',
        ]);
    }

    public function editFeature(Feature $feature): View
    {
        return view('admin.catalog.feature-form', [
            'feature' => $feature,
            'mode' => 'edit',
        ]);
    }

    public function updateFeature(Request $request, Feature $feature): RedirectResponse
    {
        $feature->update($this->validatedFeature($request, $feature));

        return redirect()
            ->route('admin.catalog.features.show', $feature)
            ->with('success', 'Feature updated.');
    }

    public function destroyFeature(Feature $feature): RedirectResponse
    {
        $feature->delete();

        return redirect()
            ->route('admin.catalog.features')
            ->with('success', 'Feature deleted.');
    }

    public function createFeatureValueGlobal(Request $request): View|RedirectResponse
    {
        $features = Feature::query()->orderBy('position')->orderBy('name')->get();

        if ($features->isEmpty()) {
            return redirect()
                ->route('admin.catalog.features.create')
                ->with('error', 'Create a feature first, then add values.');
        }

        $selectedId = $request->integer('feature_id') ?: $features->first()->id;

        return view('admin.catalog.feature-value-form', [
            'features' => $features,
            'feature' => $features->firstWhere('id', $selectedId) ?? $features->first(),
            'value' => new FeatureValue([
                'feature_id' => $selectedId,
                'position' => 0,
            ]),
            'mode' => 'create',
            'allowFeatureSelect' => true,
        ]);
    }

    public function storeFeatureValueGlobal(Request $request): RedirectResponse
    {
        $feature = Feature::query()->findOrFail($request->integer('feature_id'));
        $value = $feature->values()->create($this->validatedFeatureValue($request, $feature));

        if ($request->boolean('save_and_add')) {
            return redirect()
                ->route('admin.catalog.feature-values.create', ['feature_id' => $feature->id])
                ->with('success', 'Value “'.$value->value.'” saved. Add another.');
        }

        return redirect()
            ->route('admin.catalog.features.show', $feature)
            ->with('success', 'Feature value created.');
    }

    public function createFeatureValue(Feature $feature): View
    {
        return view('admin.catalog.feature-value-form', [
            'features' => Feature::query()->orderBy('position')->orderBy('name')->get(),
            'feature' => $feature,
            'value' => new FeatureValue(['feature_id' => $feature->id, 'position' => 0]),
            'mode' => 'create',
            'allowFeatureSelect' => true,
        ]);
    }

    public function storeFeatureValue(Request $request, Feature $feature): RedirectResponse
    {
        $featureId = $request->integer('feature_id') ?: $feature->id;
        $target = Feature::query()->findOrFail($featureId);
        $value = $target->values()->create($this->validatedFeatureValue($request, $target));

        if ($request->boolean('save_and_add')) {
            return redirect()
                ->route('admin.catalog.features.values.create', $target)
                ->with('success', 'Value “'.$value->value.'” saved. Add another.');
        }

        return redirect()
            ->route('admin.catalog.features.show', $target)
            ->with('success', 'Feature value created.');
    }

    public function editFeatureValue(Feature $feature, FeatureValue $featureValue): View
    {
        abort_unless($featureValue->feature_id === $feature->id, 404);

        return view('admin.catalog.feature-value-form', [
            'features' => Feature::query()->orderBy('position')->orderBy('name')->get(),
            'feature' => $feature,
            'value' => $featureValue,
            'mode' => 'edit',
            'allowFeatureSelect' => true,
        ]);
    }

    public function updateFeatureValue(Request $request, Feature $feature, FeatureValue $featureValue): RedirectResponse
    {
        abort_unless($featureValue->feature_id === $feature->id, 404);

        $featureId = $request->integer('feature_id') ?: $feature->id;
        $target = Feature::query()->findOrFail($featureId);
        $data = $this->validatedFeatureValue($request, $target, $featureValue);
        $data['feature_id'] = $target->id;
        $featureValue->update($data);

        return redirect()
            ->route('admin.catalog.features.show', $target)
            ->with('success', 'Feature value updated.');
    }

    public function destroyFeatureValue(Feature $feature, FeatureValue $featureValue): RedirectResponse
    {
        abort_unless($featureValue->feature_id === $feature->id, 404);
        $featureValue->delete();

        return back()->with('success', 'Feature value deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedAttributeGroup(Request $request, ?AttributeGroup $group = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'public_name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(AttributeGroup::types())],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['position'] = $data['position'] ?? ($group?->position ?? 0);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedAttributeValue(Request $request, AttributeGroup $group, ?AttributeValue $value = null): array
    {
        $data = $request->validate([
            'attribute_group_id' => ['nullable', 'exists:attribute_groups,id'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($group->type !== 'color') {
            $data['color'] = null;
        }

        $data['position'] = $data['position'] ?? ($value?->position ?? 0);
        unset($data['attribute_group_id']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedFeature(Request $request, ?Feature $feature = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['position'] = $data['position'] ?? ($feature?->position ?? 0);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedFeatureValue(Request $request, Feature $feature, ?FeatureValue $value = null): array
    {
        $data = $request->validate([
            'feature_id' => ['nullable', 'exists:features,id'],
            'value' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['position'] = $data['position'] ?? ($value?->position ?? 0);
        unset($data['feature_id']);

        return $data;
    }
}
