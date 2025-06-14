let ch = document.querySelector('.header .flex .ch');

document.querySelector('#menu-btn').onclick = ()=>{
    ch.classList.toggle('active');
}

window.onscroll = () =>{
    ch.classList.remove('active');
}

document.querySelectorAll('input[type="number"]').forEach(inputNumber =>{
    inputNumber.oninput=() =>{
        if(inputNumber.value.length > inputNumber.maxLeght) inputNumber.value=inputNumber.value.silce(0, inputNumber.maxLeght);
    };
});