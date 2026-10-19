const mysql = require('mysql');

const connection = mysql.createConnection({
  host: 'localhost',     
  user: 'root',         
  password: '',          
  database: 'restaurantes_db' 
});


connection.connect((err) => {
  if (err) {
    console.err('Error al conectar base de datos');
    
    return;
  }
  
  console.log("Conexion exitosa");
  
});


connection.query('select * from admin',(err,results)=>{

if(err){

  console.err('Error al mostrar la consulta',err.message)

  return;

}

console.log('Consulta:',results);


});







connection.end();
