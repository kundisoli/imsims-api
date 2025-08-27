import { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Plus, Tag, Trash2, LoaderCircle } from 'lucide-react';
import { type BreadcrumbItem } from '@/types';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Categories', href: '/category' },
];

interface Category {
  id: number;
  name: string;
  description: string;
}

export default function Categories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [search, setSearch] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Category | null>(null);
  const [form, setForm] = useState({ name: '', description: '' });
  const [loading, setLoading] = useState(false);

 const fetchCategories = async () => {
  try {
    // Add "await" and store the result in "res"
    const res = await fetch('/api/categories', { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
    setCategories(await res.json());
  } catch (err) {
    console.error('Failed to fetch categories:', err);
  }
};

  useEffect(() => {
    fetchCategories();
  }, []);

  /** 🔹 Open edit modal */
  const openEdit = (cat: Category) => {
    setEditing(cat);
    setForm({ name: cat.name, description: cat.description });
    setModalOpen(true);
  };

  /** 🔹 Reset modal */
  const resetModal = () => {
    setEditing(null);
    setForm({ name: '', description: '' });
    setModalOpen(false);
  };

  /** 🔹 Save category */
  const handleSave = async () => {
    try {
      setLoading(true);
      const url = editing ? `/api/categories/${editing.id}` : '/api/categories';
      const method = editing ? 'PUT' : 'POST';

      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(form),
      });

      if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
      const saved = await res.json();

      setCategories(prev =>
        editing ? prev.map(c => (c.id === saved.id ? saved : c)) : [...prev, saved]
      );

      resetModal();
    } catch (err) {
      console.error('Save failed:', err);
    } finally {
      setLoading(false);
    }
  };

  /** 🔹 Delete category */
  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this category?')) return;
    try {
      setLoading(true);
      const res = await fetch(`/api/categories/${id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
      setCategories(prev => prev.filter(c => c.id !== id));
    } catch (err) {
      console.error('Delete failed:', err);
    } finally {
      setLoading(false);
    }
  };

  /** 🔹 Filter categories */
  const filtered = categories.filter(
    c =>
      c.name.toLowerCase().includes(search.toLowerCase()) ||
      c.description.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Categories" />

      <div className="flex flex-col gap-4 p-4">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold">Category Management</h1>
            <p className="text-muted-foreground">Manage all product categories</p>
          </div>

          <Dialog open={modalOpen} onOpenChange={setModalOpen}>
            <DialogTrigger asChild>
              <Button className="flex items-center gap-2">
                <Plus className="size-4" />
                {editing ? 'Edit Category' : 'Add Category'}
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>{editing ? 'Edit Category' : 'Add Category'}</DialogTitle>
              </DialogHeader>

              <div className="flex flex-col gap-3">
                <Input
                  placeholder="Category Name"
                  value={form.name}
                  onChange={e => setForm({ ...form, name: e.target.value })}
                />
                <Input
                  placeholder="Description"
                  value={form.description}
                  onChange={e => setForm({ ...form, description: e.target.value })}
                />
              </div>

              <DialogFooter>
                <Button onClick={handleSave} disabled={loading}>
                  {loading && <LoaderCircle className="h-4 w-4 animate-spin mr-2" />}
                  {editing ? 'Save Changes' : 'Add Category'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>

        {/* Categories List */}
        <Card>
          <CardHeader>
            <CardTitle>All Categories</CardTitle>
            <div className="relative mt-2">
              <Input
                placeholder="Search categories..."
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="pl-9"
              />
              <Tag className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid gap-3">
              {filtered.length === 0 ? (
                <div className="text-center text-muted-foreground p-4">
                  No categories found.
                </div>
              ) : (
                filtered.map(cat => (
                  <div
                    key={cat.id}
                    className="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                  >
                    <div
                      className="flex items-center gap-3 cursor-pointer"
                      onClick={() => openEdit(cat)}
                    >
                      <Tag className="size-5 text-primary" />
                      <div>
                        <div className="font-medium">{cat.name}</div>
                        <div className="text-sm text-muted-foreground">{cat.description}</div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <Badge className="bg-blue-100 text-blue-800">Active</Badge>
                      <Button
                        variant="destructive"
                        size="icon"
                        onClick={() => handleDelete(cat.id)}
                        disabled={loading}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
