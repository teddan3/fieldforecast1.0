'use client';

import React, { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';

type Tab = 'pages' | 'posts' | 'blocks' | 'users';
type CmsUser = { id: number; name: string; email: string; role: string; is_active?: boolean };
type PageRecord = { id?: number; title: string; slug?: string; excerpt?: string; content?: string; template?: string; meta_title?: string; meta_description?: string; is_published?: boolean };
type PostRecord = { id?: number; title: string; slug?: string; excerpt?: string; content: string; status: string; cover_image_url?: string; meta_title?: string; meta_description?: string };
type BlockRecord = { id?: number; page_id?: number | ''; name: string; type: string; content?: string; sort_order?: number; is_active?: boolean };
type UserRecord = { id?: number; name: string; email: string; password?: string; role: string; is_active?: boolean };

const emptyPage: PageRecord = { title: '', slug: '', excerpt: '', content: '', template: 'default', meta_title: '', meta_description: '', is_published: false };
const emptyPost: PostRecord = { title: '', slug: '', excerpt: '', content: '', status: 'draft', cover_image_url: '', meta_title: '', meta_description: '' };
const emptyBlock: BlockRecord = { page_id: '', name: '', type: 'rich_text', content: '', sort_order: 0, is_active: true };
const emptyUser: UserRecord = { name: '', email: '', password: '', role: 'editor', is_active: true };

export default function AdminPage() {
  const apiBase = useMemo(() => (process.env.NEXT_PUBLIC_API_BASE_URL ?? '').replace(/\/$/, ''), []);
  const [token, setToken] = useState('');
  const [me, setMe] = useState<CmsUser | null>(null);
  const [tab, setTab] = useState<Tab>('pages');
  const [message, setMessage] = useState('');
  const [login, setLogin] = useState({ email: '', password: '' });
  const [pages, setPages] = useState<PageRecord[]>([]);
  const [posts, setPosts] = useState<PostRecord[]>([]);
  const [blocks, setBlocks] = useState<BlockRecord[]>([]);
  const [users, setUsers] = useState<UserRecord[]>([]);
  const [pageForm, setPageForm] = useState<PageRecord>(emptyPage);
  const [postForm, setPostForm] = useState<PostRecord>(emptyPost);
  const [blockForm, setBlockForm] = useState<BlockRecord>(emptyBlock);
  const [userForm, setUserForm] = useState<UserRecord>(emptyUser);

  const cmsFetch = useCallback(async (path: string, init: RequestInit = {}) => {
    const res = await fetch(`${apiBase}/api/cms${path}`, {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(init.headers ?? {}),
      },
    });

    const payload = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(payload.message ?? 'CMS request failed');
    return payload;
  }, [apiBase, token]);

  const loadAll = useCallback(async () => {
    if (!token) return;
    const [pageData, postData, blockData, userData] = await Promise.all([
      cmsFetch('/pages'),
      cmsFetch('/posts'),
      cmsFetch('/content-blocks'),
      cmsFetch('/users'),
    ]);
    setPages(pageData.pages ?? []);
    setPosts(postData.posts ?? []);
    setBlocks(blockData.blocks ?? []);
    setUsers(userData.users ?? []);
  }, [cmsFetch, token]);

  useEffect(() => {
    const saved = window.localStorage.getItem('cms_token') ?? '';
    if (saved) setToken(saved);
  }, []);

  useEffect(() => {
    if (!token) return;
    cmsFetch('/me')
      .then((data) => {
        setMe(data.user);
        void loadAll();
      })
      .catch(() => {
        window.localStorage.removeItem('cms_token');
        setToken('');
      });
  }, [cmsFetch, loadAll, token]);

  async function submitLogin(event: FormEvent) {
    event.preventDefault();
    setMessage('');
    const res = await fetch(`${apiBase}/api/cms/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(login),
    });
    const data = await res.json();
    if (!res.ok) {
      setMessage(data.message ?? 'Login failed');
      return;
    }
    window.localStorage.setItem('cms_token', data.token);
    setToken(data.token);
    setMe(data.user);
  }

  async function saveRecord<T extends { id?: number }>(resource: string, record: T, reset: () => void) {
    const path = record.id ? `/${resource}/${record.id}` : `/${resource}`;
    const method = record.id ? 'PUT' : 'POST';
    await cmsFetch(path, { method, body: JSON.stringify(record) });
    reset();
    setMessage('Saved.');
    await loadAll();
  }

  async function deleteRecord(resource: string, id?: number) {
    if (!id) return;
    await cmsFetch(`/${resource}/${id}`, { method: 'DELETE' });
    setMessage('Deleted.');
    await loadAll();
  }

  if (!apiBase) {
    return <main className="mx-auto max-w-xl p-6 text-red-200">Set NEXT_PUBLIC_API_BASE_URL before using the CMS.</main>;
  }

  if (!token) {
    return (
      <main className="mx-auto flex min-h-screen max-w-md items-center px-4">
        <form onSubmit={submitLogin} className="w-full rounded border border-zinc-800 bg-zinc-900/60 p-5">
          <h1 className="text-xl font-semibold">CMS Login</h1>
          <Field label="Email" value={login.email} onChange={(email) => setLogin({ ...login, email })} />
          <Field label="Password" type="password" value={login.password} onChange={(password) => setLogin({ ...login, password })} />
          {message ? <p className="mt-3 text-sm text-red-300">{message}</p> : null}
          <button className="mt-4 w-full rounded bg-emerald-600 px-3 py-2 text-sm font-semibold hover:bg-emerald-500">Sign in</button>
        </form>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-[1300px] px-4 py-6">
      <div className="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Field Forecast CMS</h1>
          <p className="text-sm text-zinc-400">{me ? `${me.name} · ${me.role}` : 'Content management'}</p>
        </div>
        <button
          className="rounded border border-zinc-700 px-3 py-2 text-sm hover:border-zinc-500"
          onClick={() => {
            window.localStorage.removeItem('cms_token');
            setToken('');
          }}
        >
          Sign out
        </button>
      </div>

      <div className="mb-4 flex flex-wrap gap-2">
        {(['pages', 'posts', 'blocks', 'users'] as Tab[]).map((item) => (
          <button key={item} onClick={() => setTab(item)} className={`rounded px-3 py-2 text-sm capitalize ${tab === item ? 'bg-emerald-600 text-white' : 'bg-zinc-900 text-zinc-300'}`}>
            {item}
          </button>
        ))}
      </div>

      {message ? <div className="mb-4 rounded border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-200">{message}</div> : null}

      {tab === 'pages' ? (
        <Editor title="Pages" form={<PageForm value={pageForm} setValue={setPageForm} onSave={() => saveRecord('pages', pageForm, () => setPageForm(emptyPage))} />} list={<RecordList records={pages} label={(p) => p.title} onEdit={setPageForm} onDelete={(p) => deleteRecord('pages', p.id)} />} />
      ) : null}
      {tab === 'posts' ? (
        <Editor title="Posts" form={<PostForm value={postForm} setValue={setPostForm} onSave={() => saveRecord('posts', postForm, () => setPostForm(emptyPost))} />} list={<RecordList records={posts} label={(p) => `${p.title} · ${p.status}`} onEdit={setPostForm} onDelete={(p) => deleteRecord('posts', p.id)} />} />
      ) : null}
      {tab === 'blocks' ? (
        <Editor title="Content Blocks" form={<BlockForm pages={pages} value={blockForm} setValue={setBlockForm} onSave={() => saveRecord('content-blocks', blockForm, () => setBlockForm(emptyBlock))} />} list={<RecordList records={blocks} label={(b) => `${b.name} · ${b.type}`} onEdit={setBlockForm} onDelete={(b) => deleteRecord('content-blocks', b.id)} />} />
      ) : null}
      {tab === 'users' ? (
        <Editor title="Users" form={<UserForm value={userForm} setValue={setUserForm} onSave={() => saveRecord('users', userForm, () => setUserForm(emptyUser))} />} list={<RecordList records={users} label={(u) => `${u.name} · ${u.role}`} onEdit={(u) => setUserForm({ ...u, password: '' })} onDelete={(u) => deleteRecord('users', u.id)} />} />
      ) : null}
    </main>
  );
}

function Editor({ title, form, list }: { title: string; form: React.ReactNode; list: React.ReactNode }) {
  return (
    <section className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_380px]">
      <div className="rounded border border-zinc-800 bg-zinc-900/40 p-4">
        <h2 className="mb-3 text-lg font-semibold">{title}</h2>
        {form}
      </div>
      <div className="rounded border border-zinc-800 bg-zinc-900/40 p-4">{list}</div>
    </section>
  );
}

function RecordList<T extends { id?: number }>({ records, label, onEdit, onDelete }: { records: T[]; label: (record: T) => string; onEdit: (record: T) => void; onDelete: (record: T) => void }) {
  return (
    <div className="space-y-2">
      {records.map((record) => (
        <div key={record.id} className="flex items-center justify-between gap-3 rounded border border-zinc-800 p-3">
          <span className="min-w-0 truncate text-sm">{label(record)}</span>
          <div className="flex gap-2">
            <button className="rounded bg-zinc-800 px-2 py-1 text-xs hover:bg-zinc-700" onClick={() => onEdit(record)}>Edit</button>
            <button className="rounded bg-red-950 px-2 py-1 text-xs text-red-200 hover:bg-red-900" onClick={() => onDelete(record)}>Delete</button>
          </div>
        </div>
      ))}
    </div>
  );
}

function PageForm({ value, setValue, onSave }: { value: PageRecord; setValue: (value: PageRecord) => void; onSave: () => void }) {
  return <FormShell onSave={onSave}><Field label="Title" value={value.title} onChange={(title) => setValue({ ...value, title })} /><Field label="Slug" value={value.slug ?? ''} onChange={(slug) => setValue({ ...value, slug })} /><Textarea label="Excerpt" value={value.excerpt ?? ''} onChange={(excerpt) => setValue({ ...value, excerpt })} /><Textarea label="Content" value={value.content ?? ''} onChange={(content) => setValue({ ...value, content })} rows={10} /><Field label="Meta title" value={value.meta_title ?? ''} onChange={(meta_title) => setValue({ ...value, meta_title })} /><Textarea label="Meta description" value={value.meta_description ?? ''} onChange={(meta_description) => setValue({ ...value, meta_description })} /><Check label="Published" checked={!!value.is_published} onChange={(is_published) => setValue({ ...value, is_published })} /></FormShell>;
}

function PostForm({ value, setValue, onSave }: { value: PostRecord; setValue: (value: PostRecord) => void; onSave: () => void }) {
  return <FormShell onSave={onSave}><Field label="Title" value={value.title} onChange={(title) => setValue({ ...value, title })} /><Field label="Slug" value={value.slug ?? ''} onChange={(slug) => setValue({ ...value, slug })} /><Select label="Status" value={value.status} options={['draft', 'published', 'archived']} onChange={(status) => setValue({ ...value, status })} /><Textarea label="Excerpt" value={value.excerpt ?? ''} onChange={(excerpt) => setValue({ ...value, excerpt })} /><Textarea label="Content" value={value.content} onChange={(content) => setValue({ ...value, content })} rows={12} /><Field label="Cover image URL" value={value.cover_image_url ?? ''} onChange={(cover_image_url) => setValue({ ...value, cover_image_url })} /></FormShell>;
}

function BlockForm({ pages, value, setValue, onSave }: { pages: PageRecord[]; value: BlockRecord; setValue: (value: BlockRecord) => void; onSave: () => void }) {
  return <FormShell onSave={onSave}><Select label="Page" value={String(value.page_id ?? '')} options={['', ...pages.map((p) => String(p.id))]} labels={{ '': 'Global block', ...Object.fromEntries(pages.map((p) => [String(p.id), p.title])) }} onChange={(page_id) => setValue({ ...value, page_id: page_id ? Number(page_id) : '' })} /><Field label="Name" value={value.name} onChange={(name) => setValue({ ...value, name })} /><Select label="Type" value={value.type} options={['hero', 'rich_text', 'cta', 'faq', 'html']} onChange={(type) => setValue({ ...value, type })} /><Textarea label="Content" value={value.content ?? ''} onChange={(content) => setValue({ ...value, content })} rows={10} /><Field label="Sort order" type="number" value={String(value.sort_order ?? 0)} onChange={(sort_order) => setValue({ ...value, sort_order: Number(sort_order) })} /><Check label="Active" checked={!!value.is_active} onChange={(is_active) => setValue({ ...value, is_active })} /></FormShell>;
}

function UserForm({ value, setValue, onSave }: { value: UserRecord; setValue: (value: UserRecord) => void; onSave: () => void }) {
  return <FormShell onSave={onSave}><Field label="Name" value={value.name} onChange={(name) => setValue({ ...value, name })} /><Field label="Email" value={value.email} onChange={(email) => setValue({ ...value, email })} /><Field label="Password" type="password" value={value.password ?? ''} onChange={(password) => setValue({ ...value, password })} /><Select label="Role" value={value.role} options={['admin', 'editor', 'viewer']} onChange={(role) => setValue({ ...value, role })} /><Check label="Active" checked={!!value.is_active} onChange={(is_active) => setValue({ ...value, is_active })} /></FormShell>;
}

function FormShell({ children, onSave }: { children: React.ReactNode; onSave: () => void }) {
  return <form className="grid gap-3" onSubmit={(event) => { event.preventDefault(); void onSave(); }}>{children}<button className="rounded bg-emerald-600 px-3 py-2 text-sm font-semibold hover:bg-emerald-500">Save</button></form>;
}

function Field({ label, value, onChange, type = 'text' }: { label: string; value: string; onChange: (value: string) => void; type?: string }) {
  return <label className="grid gap-1 text-xs text-zinc-300">{label}<input type={type} className="rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-emerald-500" value={value} onChange={(event) => onChange(event.target.value)} /></label>;
}

function Textarea({ label, value, onChange, rows = 4 }: { label: string; value: string; onChange: (value: string) => void; rows?: number }) {
  return <label className="grid gap-1 text-xs text-zinc-300">{label}<textarea rows={rows} className="rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-emerald-500" value={value} onChange={(event) => onChange(event.target.value)} /></label>;
}

function Select({ label, value, options, labels = {}, onChange }: { label: string; value: string; options: string[]; labels?: Record<string, string>; onChange: (value: string) => void }) {
  return <label className="grid gap-1 text-xs text-zinc-300">{label}<select className="rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-emerald-500" value={value} onChange={(event) => onChange(event.target.value)}>{options.map((option) => <option key={option} value={option}>{labels[option] ?? option}</option>)}</select></label>;
}

function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (checked: boolean) => void }) {
  return <label className="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />{label}</label>;
}
