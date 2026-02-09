import { createClient } from '@supabase/supabase-js';
import { NextResponse } from 'next/server';

// Initialize Admin Client (runs only on server)
const supabaseAdmin = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.SUPABASE_SERVICE_ROLE_KEY!,
  {
    auth: { autoRefreshToken: false, persistSession: false }
  }
);

export async function POST(req: Request) {
  try {
    const { table, data } = await req.json();
    
    if (!table || !data) {
      return NextResponse.json({ error: 'Table and data are required' }, { status: 400 });
    }

    const isBulk = Array.isArray(data);
    let query = supabaseAdmin.from(table).insert(data).select();
    
    if (!isBulk) {
      query = query.single();
    }

    const { data: result, error } = await query;

    if (error) {
      console.error(`Admin DB API Error (Insert into ${table}):`, error);
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true, data: result });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function PUT(req: Request) {
  try {
    const { table, id, data } = await req.json();
    
    if (!table || !id || !data) {
      return NextResponse.json({ error: 'Table, ID and data are required' }, { status: 400 });
    }

    const { data: result, error } = await supabaseAdmin
      .from(table)
      .update(data)
      .eq('id', id)
      .select()
      .single();

    if (error) {
      console.error(`Admin DB API Error (Update ${table}):`, error);
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true, data: result });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function DELETE(req: Request) {
  try {
    const { table, id } = await req.json();
    
    if (!table || !id) {
      return NextResponse.json({ error: 'Table and ID are required' }, { status: 400 });
    }

    const { error } = await supabaseAdmin
      .from(table)
      .delete()
      .eq('id', id);

    if (error) {
      console.error(`Admin DB API Error (Delete from ${table}):`, error);
      return NextResponse.json({ error: error.message }, { status: 500 });
    }

    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
