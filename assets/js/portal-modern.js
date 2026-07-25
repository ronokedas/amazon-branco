(function(){
    'use strict';
    const button=document.querySelector('.portal-menu-button');
    const nav=document.querySelector('.portal-nav');
    if(button&&nav){
        button.addEventListener('click',function(){
            const open=nav.classList.toggle('is-open');
            button.setAttribute('aria-expanded',open?'true':'false');
            button.setAttribute('aria-label',open?'Fechar menu':'Abrir menu');
        });
        nav.addEventListener('click',function(event){
            if(event.target.closest('a')){
                nav.classList.remove('is-open');
                button.setAttribute('aria-expanded','false');
                button.setAttribute('aria-label','Abrir menu');
            }
        });
    }
    document.querySelectorAll('[data-portal-upload]').forEach(function(form){
        const input=form.querySelector('input[type="file"]');
        const zone=form.querySelector('.portal-upload-zone');
        const list=form.querySelector('.portal-file-list');
        const empty=form.querySelector('.portal-file-empty');
        const count=form.querySelector('[data-file-count]');
        const submit=form.querySelector('button[type="submit"]');
        const max=50*1024*1024;
        if(!input||!zone||!list||!submit)return;
        function render(){
            const files=Array.from(input.files||[]);
            list.innerHTML='';
            if(count)count.textContent=String(files.length);
            if(empty)empty.hidden=files.length>0;
            files.forEach(function(file){
                const item=document.createElement('li');
                const name=document.createElement('span');
                const size=document.createElement('small');
                name.textContent=file.name;
                size.textContent=(file.size/1024/1024).toFixed(2)+' MB';
                if(file.size>max){size.textContent+=' · excede 50 MB';size.style.color='#b42318'}
                item.append(name,size);list.appendChild(item);
            });
        }
        zone.addEventListener('click',function(){input.click()});
        zone.addEventListener('keydown',function(event){if(event.key==='Enter'||event.key===' '){event.preventDefault();input.click()}});
        input.addEventListener('change',render);
        ['dragenter','dragover'].forEach(function(name){zone.addEventListener(name,function(event){event.preventDefault();zone.classList.add('is-dragging')})});
        ['dragleave','drop'].forEach(function(name){zone.addEventListener(name,function(event){event.preventDefault();zone.classList.remove('is-dragging')})});
        zone.addEventListener('drop',function(event){if(event.dataTransfer&&event.dataTransfer.files.length){input.files=event.dataTransfer.files;render()}});
        form.addEventListener('submit',function(event){
            const files=Array.from(input.files||[]);
            if(!files.length||files.some(function(file){return file.size>max})){
                event.preventDefault();input.focus();
                window.alert(!files.length?'Selecione pelo menos um arquivo.':'Cada arquivo deve ter no máximo 50 MB.');
                return;
            }
            submit.disabled=true;
            submit.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Enviando revisão...';
        });
    });
})();
