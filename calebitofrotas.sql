/////////////////////////////////////////////////////
// USERS
/////////////////////////////////////////////////////

Table users {
  id int [pk, increment]
  name varchar(150)
  phone varchar(20)
  email varchar(150)
  role varchar(50) // motorista, ajudante, gestor, administrador
  status varchar(20) // ativo, inativo
  password_hash varchar(255)
  photo_url varchar(255)
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// VEHICLES
/////////////////////////////////////////////////////

Table vehicles {
  id int [pk, increment]
  plate_number varchar(20) [unique]
  model varchar(100)
  status varchar(20) // ativo, inativo, manutencao
  current_km bigint
  photo_url varchar(255) // **imagen del vehículo**
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// STORES
/////////////////////////////////////////////////////

Table stores {
  id int [pk, increment]
  name varchar(100)
  address varchar(255)
  city varchar(100)
  state varchar(50)
  maps_url varchar(255)
  image_url varchar(255) // **imagen de la tienda**
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// DESTINATIONS
/////////////////////////////////////////////////////

Table destinations {
  id int [pk, increment]
  name varchar(150)
  address varchar(255)
  city varchar(100)
  state varchar(50)
  latitude decimal(10,7)
  longitude decimal(10,7)
  type varchar(50) // store, client, depot, other
  maps_url varchar(255)
  image_url varchar(255) // **imagen del destino**
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// TASKS
/////////////////////////////////////////////////////

Table tasks {
  id int [pk, increment]
  title varchar(150)
  description text
  priority varchar(20) // normal, importante, urgente
  status varchar(20) // em_espera, em_processo, concluida
  store_id int [ref: > stores.id]
  destination_id int [ref: > destinations.id]
  created_by int [ref: > users.id]
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// TASK ASSIGNMENTS
/////////////////////////////////////////////////////

Table task_assignments {
  id int [pk, increment]
  task_id int [ref: > tasks.id]
  user_id int [ref: > users.id]
  role varchar(20) // motorista, ajudante
  assigned_at datetime
}

/////////////////////////////////////////////////////
// ROUTES
/////////////////////////////////////////////////////

Table routes {
  id int [pk, increment]
  date date
  priority varchar(20) // normal, importante, urgente
  status varchar(20) // em_espera, em_processo, concluida
  store_id int [ref: > stores.id]
  destination_id int [ref: > destinations.id]
  created_by int [ref: > users.id]
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// ROUTE ASSIGNMENTS
/////////////////////////////////////////////////////

Table route_assignments {
  id int [pk, increment]
  route_id int [ref: > routes.id]
  user_id int [ref: > users.id]
  role varchar(20) // motorista, ajudante
  assigned_at datetime
}

/////////////////////////////////////////////////////
// VEHICLE USAGES (KM)
/////////////////////////////////////////////////////

Table vehicle_usages {
  id int [pk, increment]
  vehicle_id int [ref: > vehicles.id]
  user_id int [ref: > users.id]
  route_id int [ref: > routes.id]
  start_km bigint
  end_km bigint
  date datetime
  notes varchar(255)
  created_at datetime
}

/////////////////////////////////////////////////////
// MAINTENANCE ITEMS
/////////////////////////////////////////////////////

Table maintenance_items {
  id int [pk, increment]
  name varchar(100)
  description varchar(255)
  interval_km int
  interval_months int
  created_at datetime
  updated_at datetime
}

/////////////////////////////////////////////////////
// VEHICLE MAINTENANCES
/////////////////////////////////////////////////////

Table vehicle_maintenances {
  id int [pk, increment]
  vehicle_id int [ref: > vehicles.id]
  maintenance_item_id int [ref: > maintenance_items.id]
  performed_at_date date
  performed_at_km bigint
  next_due_km bigint
  next_due_date date
  notes varchar(255)
  created_by int [ref: > users.id]
  created_at datetime
}

/////////////////////////////////////////////////////
// MAINTENANCE ALERTS
/////////////////////////////////////////////////////

Table maintenance_alerts {
  id int [pk, increment]
  vehicle_id int [ref: > vehicles.id]
  maintenance_item_id int [ref: > maintenance_items.id]
  message varchar(255)
  triggered_at datetime
  is_resolved bool
  resolved_at datetime
}

/////////////////////////////////////////////////////
// NOTIFICATIONS
/////////////////////////////////////////////////////

Table notifications {
  id int [pk, increment]
  user_id int [ref: > users.id]
  message varchar(255)
  is_read bool
  created_at datetime
}
