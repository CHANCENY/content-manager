(function (){

    alert('hh')
   function search(search_key) {
       console.log(search_key);
       if (document.querySelector('#default-admin-search')) {
           document.querySelector('#default-admin-search').addEventListener('keydown', (e) => {
               console.log(e.type, e.key, e.keyCode);
           })
       }
   }

   window.search_api = search;
})();

console.log('hehehe')